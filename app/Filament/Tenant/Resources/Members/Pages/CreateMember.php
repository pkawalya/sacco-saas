<?php

namespace App\Filament\Tenant\Resources\Members\Pages;

use App\Filament\Tenant\Resources\Members\MemberResource;
use App\Services\MemberNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected array $kycDocuments = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['member_number'] = MemberNumberGenerator::generate($data['branch_code'] ?? null);
        $data['registered_by'] = auth()->id();

        // Extract KYC documents
        $this->kycDocuments = $data['kyc_documents'] ?? [];
        unset($data['kyc_documents']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $member = $this->record;

        // Create KYC documents
        foreach ($this->kycDocuments as $doc) {
            if (! empty($doc['document_name']) && ! empty($doc['document_file'])) {
                MemberDocument::create([
                    'member_id' => $member->id,
                    'document_type' => 'kyc', // or based on name
                    'file_path' => $doc['document_file'],
                    'upload_date' => now(),
                    'verification_status' => 'pending',
                ]);
            }
        }
    }
}
