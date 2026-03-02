<?php

namespace App\Exports;

use App\Models\Cart;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CartExport implements FromCollection ,WithHeadings
{
    protected Collection $rows;

    /**
     * @return \Illuminate\Support\Collection
     */
    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }
    public function collection(): Collection
    {
        return $this->rows->map(fn (Cart $cart) => $this->tableBody($cart));
    }
    public function headings(): array
    {
        return [
            'Client Name',
            'Phone',
            'Products',
            'Quantity',
            'Price',
            'Date',
        ];
    }

    private function tableBody(Cart $cart): array
    {
        $cartProducts = [];
        $cartQty = 0;
        if(!empty($cart->userCart->cartDetail->cartDetailProducts)){
            foreach ($cart->userCart->cartDetail->cartDetailProducts as $cartDetailProduct) {
                $productName = $cartDetailProduct?->Stock?->product?->translation->title;
                $cartProducts[] = $productName;
                $cartQty += $cartDetailProduct->quantity;
            }
        }

        $productNames = implode(', ', $cartProducts);

        return [
            'Client Name' => $cart?->userCart?->name,
            'Phone' => $cart?->user?->phone,
            'Products' => $productNames,
            'Quantity' => $cartQty,
            'Price' => $cart?->total_price,
            'Date' => $cart?->created_at,
        ];
    }
}
