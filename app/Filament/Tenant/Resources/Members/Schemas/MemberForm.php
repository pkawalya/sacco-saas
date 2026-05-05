<?php

namespace App\Filament\Tenant\Resources\Members\Schemas;

use App\Models\Tenant\Member;
use App\Services\MemberNumberGenerator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->columns(3)
                    ->schema([
                        TextInput::make('member_number')
                            ->label('Member Number')
                            ->default(fn (): string => MemberNumberGenerator::generate())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('middle_name')
                            ->maxLength(100),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(100),
                        DatePicker::make('date_of_birth')
                            ->required()
                            ->maxDate(now()->subYears(18))
                            ->native(false),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->required(),
                        TextInput::make('nationality')
                            ->default('Ugandan')
                            ->required(),
                        Select::make('national_id_type')
                            ->options([
                                'national_id' => 'National ID',
                                'passport' => 'Passport',
                                'driving_permit' => 'Driving Permit',
                                'refugee_id' => 'Refugee ID',
                            ])
                            ->default('national_id')
                            ->required(),
                        TextInput::make('national_id_number')
                            ->label('National NIN Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        FileUpload::make('photo_path')
                            ->label('Passport Photo')
                            ->image()
                            ->avatar()
                            ->directory('member-photos')
                            ->columnSpanFull(),
                    ]),

                Section::make('KYC Documents')
                    ->columns(1)
                    ->schema([
                        Repeater::make('kyc_documents')
                            ->label('KYC Documents')
                            ->schema([
                                TextInput::make('document_name')
                                    ->label('Document Name')
                                    ->required()
                                    ->placeholder('e.g. National ID, Passport'),
                                FileUpload::make('document_file')
                                    ->label('Document Upload')
                                    ->required()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                    ->maxSize(5120) // 5MB
                                    ->directory('kyc-documents')
                                    ->uploadProgressIndicatorPosition('left'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add KYC Document')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['document_name'] ?? null),
                    ]),

                Section::make('Contact Details')
                    ->columns(3)
                    ->schema([
                        TextInput::make('primary_phone')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                        TextInput::make('secondary_phone')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(150),
                        TextInput::make('physical_address')
                            ->maxLength(255),
                        TextInput::make('village')
                            ->maxLength(100),
                        TextInput::make('cell')
                            ->maxLength(100),
                        TextInput::make('district')
                            ->maxLength(100),
                        TextInput::make('postal_address')
                            ->maxLength(255),
                    ]),

                Section::make('Employment & Income')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextInput::make('occupation')
                            ->maxLength(100),
                        TextInput::make('employer_name')
                            ->maxLength(150),
                        Select::make('monthly_income_range')
                            ->options([
                                'below_200k' => 'Below UGX 200,000',
                                '200k_500k' => 'UGX 200,000 – 500,000',
                                '500k_1m' => 'UGX 500,000 – 1,000,000',
                                '1m_3m' => 'UGX 1,000,000 – 3,000,000',
                                '3m_5m' => 'UGX 3,000,000 – 5,000,000',
                                '5m_10m' => 'UGX 5,000,000 – 10,000,000',
                                'above_10m' => 'Above UGX 10,000,000',
                            ]),
                    ]),

                Section::make('Next of Kin')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextInput::make('nok_name')
                            ->label('Full Name')
                            ->maxLength(200),
                        Select::make('nok_relationship')
                            ->label('Relationship')
                            ->options([
                                'spouse' => 'Spouse',
                                'parent' => 'Parent',
                                'child' => 'Child',
                                'sibling' => 'Sibling',
                                'guardian' => 'Guardian',
                                'other' => 'Other',
                            ]),
                        TextInput::make('nok_contact')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20),
                    ]),

                Section::make('Next of Kin Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nok_name')
                            ->label('Next of Kin Full Name')
                            ->required()
                            ->maxLength(150),
                        Select::make('nok_gender')
                            ->label('Next of Kin Gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Select::make('nok_relationship')
                            ->label('Next of Kin Relationship to Member')
                            ->options([
                                'spouse' => 'Spouse',
                                'parent' => 'Parent',
                                'sibling' => 'Sibling',
                                'child' => 'Child',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TextInput::make('nok_national_id_number')
                            ->label('Next of Kin National ID Number (NIN)')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g. CM1234567890123'),
                        FileUpload::make('nok_national_id_document')
                            ->label('Next of Kin National ID Document Upload')
                            ->required()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->directory('nok-documents')
                            ->uploadProgressIndicatorPosition('left'),
                        Select::make('nok_marital_status')
                            ->label('Next of Kin Marital Status')
                            ->options([
                                'single' => 'Single',
                                'married' => 'Married',
                                'divorced' => 'Divorced',
                                'widowed' => 'Widowed',
                                'separated' => 'Separated',
                            ])
                            ->required(),
                    ]),

                Section::make('Membership Intention')
                    ->columns(2)
                    ->schema([
                        Select::make('member_intention')
                            ->label('Member Intention')
                            ->options([
                                'savings' => 'Savings',
                                'loan' => 'Loan',
                                'both' => 'Both',
                            ])
                            ->required()
                            ->reactive(),
                        TextInput::make('willing_weekly_savings_amount')
                            ->label('Willing Weekly Savings Amount')
                            ->numeric()
                            ->prefix('UGX')
                            ->minValue(1000)
                            ->visible(fn (callable $get) => in_array($get('member_intention'), ['savings', 'both']))
                            ->required(fn (callable $get) => in_array($get('member_intention'), ['savings', 'both'])),
                    ]),

                Section::make('Classification & Settings')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Select::make('member_category')
                            ->options([
                                'individual' => 'Individual',
                                'group' => 'Group Member',
                                'corporate' => 'Corporate',
                                'staff' => 'Staff',
                                'bumu' => 'Bumu Member',
                            ])
                            ->default('individual')
                            ->required(),
                        Select::make('referral_source')
                            ->options([
                                'self' => 'Self Registration',
                                'staff_referral' => 'Staff Referral',
                                'agent' => 'Agent',
                                'mobile_app' => 'Mobile App',
                                'employer' => 'Employer',
                                'member_referral' => 'Member Referral',
                            ]),
                        TextInput::make('branch_code')
                            ->maxLength(20),
                        Select::make('status')
                            ->options(collect(Member::STATUSES)->mapWithKeys(
                                fn (string $status): array => [$status => ucfirst($status)]
                            )->toArray())
                            ->default('applicant')
                            ->required()
                            ->visibleOn('edit'),
                    ]),
            ]);
    }
}
