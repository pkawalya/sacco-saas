<?php

namespace App\Filament\Tenant\Resources\Loans\Pages;

use App\Filament\Tenant\Resources\Loans\LoanResource;
use App\Models\Tenant\LoanCollateral;
use App\Models\Tenant\LoanProduct;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract collateral data
        $collateralType = $data['collateral_type'] ?? null;
        if ($collateralType === 'other') {
            $collateralType = $data['collateral_type_other'] ?? 'other';
        }

        $collateralData = [
            'collateral_name' => $data['collateral_name'] ?? null,
            'collateral_type' => $collateralType,
            'collateral_location' => $data['collateral_location'] ?? null,
            'collateral_value' => $data['collateral_value'] ?? null,
            'collateral_documents' => $data['collateral_documents'] ?? [],
        ];

        // Store in session or property for afterCreate
        $this->collateralData = $collateralData;

        // Remove from loan data
        unset(
            $data['collateral_name'],
            $data['collateral_type'],
            $data['collateral_type_other'],
            $data['collateral_location'],
            $data['collateral_value'],
            $data['collateral_documents']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $loan = $this->record;
        $data = $this->collateralData ?? [];

        // Check if collateral is required
        $product = LoanProduct::find($loan->product_id);
        if (! $product || ! $product->collateral_required || empty($data['collateral_name'])) {
            return;
        }

        // Create collateral
        LoanCollateral::create([
            'loan_id' => $loan->id,
            'member_id' => $loan->member_id,
            'asset_type' => $data['collateral_type'],
            'asset_description' => $data['collateral_name'],
            'location' => $data['collateral_location'],
            'estimated_value' => $data['collateral_value'],
            'documents' => $data['collateral_documents'],
            'status' => LoanCollateral::STATUS_ACTIVE,
        ]);

        // Note: File uploads are handled automatically by Filament, stored in storage/app/public/loans/collateral
        // You may need to associate them with the collateral record if required
    }
}
