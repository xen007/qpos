<?php
namespace App\Exports;
use Maatwebsite\Excel\Concerns\{FromArray,WithHeadings,WithCustomValueBinder};
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
final class PurchaseOrdersExport extends StringValueBinder implements FromArray,WithHeadings,WithCustomValueBinder {
 public function __construct(private array $rows,private array $columns){}
 public function array():array{return $this->rows;}
 public function headings():array{return $this->columns;}
}
