<?php

namespace LaravelLiberu\DataExport\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaravelLiberu\DataExport\Contracts\CustomCount;
use LaravelLiberu\DataExport\Contracts\ExportsExcel as AsyncExcel;
use LaravelLiberu\DataExport\Enums\Statuses;
use LaravelLiberu\DataExport\Exceptions\Exception;
use LaravelLiberu\DataExport\Notifications\ExportDone;
use LaravelLiberu\DataExport\Services\ExcelExport as AsyncExporter;
use LaravelLiberu\Excel\Contracts\ExportsExcel as SyncExcel;
use LaravelLiberu\Excel\Services\ExcelExport as SyncExporter;
use LaravelLiberu\Files\Contracts\Attachable;
use LaravelLiberu\Files\Contracts\CascadesFileDeletion;
use LaravelLiberu\Files\Models\File;
use LaravelLiberu\Files\Models\Type;
use LaravelLiberu\Helpers\Services\Decimals;
use LaravelLiberu\IO\Contracts\IOOperation;
use LaravelLiberu\IO\Enums\IOTypes;
use LaravelLiberu\Tables\Notifications\ExportStarted;
use LaravelLiberu\TrackWho\Traits\CreatedBy;
use UnexpectedValueException;

class Export extends Model implements
    Attachable,
    IOOperation,
    CascadesFileDeletion
{
    use CreatedBy, HasFactory;

    protected $guarded = [];

    protected $table = 'data_exports';

    public function file(): Relation
    {
        return $this->belongsTo(File::class);
    }

    public static function cascadeFileDeletion(File $file): void
    {
        self::whereFileId($file->id)->first()->delete();
    }

    public function cancel(): void
    {
        if (! $this->running()) {
            throw Exception::cannotBeCancelled();
        }

        $this->update(['status' => Statuses::Cancelled]);
    }

    public function cancelled(): bool
    {
        return $this->status === Statuses::Cancelled;
    }

    public function failed(): bool
    {
        return $this->status === Statuses::Failed;
    }

    public function running(): bool
    {
        return in_array($this->status, Statuses::running());
    }

    public function finalized(): bool
    {
        return $this->status === Statuses::Finalized;
    }

    public function operationType(): int
    {
        return IOTypes::Export;
    }

    public function status(): int
    {
        return $this->running()
            ? $this->status
            : Statuses::Finalized;
    }

    public function progress(): ?int
    {
        if (! $this->total) {
            return null;
        }

        $div = Decimals::div($this->entries, $this->total);

        return (int) Decimals::mul($div, 100);
    }

    public function broadcastWith(): array
    {
        return [
            'name' => $this->name,
            'entries' => $this->entries,
            'total' => $this->total,
        ];
    }

    public function createdAt(): Carbon
    {
        return $this->created_at;
    }

    public static function excel($exporter): self
    {
        if ($exporter instanceof AsyncExcel) {
            return self::asyncExcel($exporter);
        }
        if ($exporter instanceof SyncExcel) {
            return self::syncExcel($exporter);
        }

        throw new UnexpectedValueException(
            __('The exporter class must be in instance of ExportsExcel interface')
        );
    }

    private static function asyncExcel(AsyncExcel $exporter): self
    {
        $export = self::factory()->create([
            'name' => $exporter->filename(),
            'total' => $exporter instanceof CustomCount
                ? $exporter->count()
                : $exporter->query()->count(),
        ]);

        (new AsyncExporter($export, $exporter))->handle();

        return $export;
    }

    private static function syncExcel(SyncExcel $exporter): self
    {
        $export = self::factory()->create([
            'name' => $exporter->filename(),
            'status' => Statuses::Processing,
            'total' => 0,
        ]);

        $export->createdBy->notify((new ExportStarted($export->name))
            ->onQueue('notifications'));

        $count = Collection::wrap($exporter->sheets())
            ->reduce(fn ($total, $sheet) => $total += is_countable($exporter->rows($sheet)) ? count($exporter->rows($sheet)) : 0, 0);

        $export->updateProgress($count);

        $location = Str::afterLast((new SyncExporter($exporter))->save(), 'app/');
        $filename = Str::afterLast($location, '/');

        Storage::move($location, Type::for(self::class)->path($filename));

        $args = [$export, $filename, $exporter->filename(), $export->created_by];
        $file = File::attach(...$args);

        $export->fill(['status' => Statuses::Finalized])
            ->file()->associate($file)
            ->save();

        $subject = method_exists($exporter, 'emailSubject')
            ? $exporter->emailSubject($export)
            : __(':name export done', ['name' => $export->name]);

        $export->createdBy->notify((new ExportDone($export, $subject))
            ->onQueue('notifications'));

        return $export;
    }

    public function updateProgress(int $entries)
    {
        $this->entries += $entries;
        $this->total = max($this->total, $this->entries);
        $this->save();
    }

    public function delete()
    {
        if (! Statuses::isDeletable($this->status)) {
            throw Exception::deleteRunningExport();
        }

        $response = parent::delete();

        $this->file?->delete();

        return $response;
    }

    public function scopeExpired(Builder $query): Builder
    {
        $retainFor = Config::get('liberu.exports.retainFor');
        $expired = Carbon::today()->subDays($retainFor);

        return $query->where('created_at', '<', $expired);
    }

    public function scopeDeletable(Builder $query): Builder
    {
        return $query->whereIn('status', Statuses::deletable());
    }

    public function scopeNotDeletable(Builder $query): Builder
    {
        return $query->whereNotIn('status', Statuses::deletable());
    }
}
