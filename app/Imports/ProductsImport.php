<?php
namespace App\Imports;

use App\Services\ProductImportService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\{ToCollection,WithHeadingRow,SkipsEmptyRows};

/** Compatibility adapter: every import uses the same strict service. */
class ProductsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(
        private readonly ?int $supplierId,
        private readonly int $userId,
        private readonly int $shopId,
        private readonly string $mode = 'catalogue',
        private readonly string $evidence = '',
        private readonly ?string $operationKey = null,
    ) {}
    public function collection(Collection $rows): void
    {
        $path=tempnam(sys_get_temp_dir(),'qpos-import-');
        if ($path===false) throw new \RuntimeException('Cannot prepare the import.');
        try {
            $stream=fopen($path,'wb');
            fputcsv($stream,ProductImportService::HEADERS,',','"','');
            foreach ($rows as $row) {
                $values=$row instanceof Collection ? $row->all() : $row;
                // Spreadsheet numeric coercion cannot prove leading-zero SKUs or exact decimals.
                if (!is_string($values['sku'] ?? null)) throw ValidationException::withMessages(['sku'=>__('Import SKUs as text using CSV.')]);
                $line=[];
                foreach (ProductImportService::HEADERS as $header) {
                    $value=$values[$header] ?? '';
                    if (is_float($value)) throw ValidationException::withMessages([$header=>__('Use CSV text for exact decimal values.')]);
                    $line[]=(string)$value;
                }
                fputcsv($stream,$line,',','"','');
            }
            fclose($stream); $stream=null;
            app(ProductImportService::class)->apply($path,[
                'shop_id'=>$this->shopId,'user_id'=>$this->userId,'mode'=>$this->mode,
                'supplier_id'=>$this->supplierId,'evidence'=>$this->evidence,
            ],$this->operationKey ?? (string)\Illuminate\Support\Str::uuid());
        } finally {
            if (isset($stream) && is_resource($stream)) fclose($stream);
            if (is_file($path)) unlink($path);
        }
    }
}
