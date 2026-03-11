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
