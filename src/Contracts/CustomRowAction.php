<?php

namespace LaravelLiberu\DataExport\Contracts;

use Box\Spout\Writer\XLSX\Writer;

interface CustomRowAction
{
    public function customRowAction(Writer $writer, $row): void;
}
