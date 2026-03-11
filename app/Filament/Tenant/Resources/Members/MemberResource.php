<?php

namespace App\Filament\Tenant\Resources\Members;

use App\Filament\Tenant\Concerns\BelongsToModule;
use App\Filament\Tenant\Resources\Members\Pages\CreateMember;
use App\Filament\Tenant\Resources\Members\Pages\EditMember;
use App\Filament\Tenant\Resources\Members\Pages\ListMembers;
use App\Filament\Tenant\Resources\Members\Pages\ViewMember;
use App\Filament\Tenant\Resources\Members\Schemas\MemberForm;
use App\Filament\Tenant\Resources\Members\Tables\MembersTable;
use App\Models\Tenant\Member;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MemberResource extends Resource
{
    use BelongsToModule;

    protected static ?string $model = Member::class;

    protected static string $moduleKey = 'member_management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Personal Information')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('photo_path')
                            ->label('Photo')
                            ->circular()
                            ->defaultImageUrl(fn (): string => 'https://ui-avatars.com/api/?name=M&color=7F9CF5&background=EBF4FF')
                            ->columnSpan(1),
                        Grid::make(2)
                            ->columnSpan(2)
                            ->schema([
                                TextEntry::make('member_number')
                                    ->label('Member #')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable(),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'applicant' => 'warning',
                                        'dormant' => 'gray',
                                        'suspended' => 'danger',
                                        'deceased' => 'gray',
                                        'exited' => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('full_name')
                                    ->label('Full Name'),
                                TextEntry::make('date_of_birth')
                                    ->date(),
                                TextEntry::make('gender'),
                                TextEntry::make('nationality'),
                                TextEntry::make('national_id_type')
                                    ->label('ID Type'),
                                TextEntry::make('national_id_number')
                                    ->label('ID Number')
                                    ->copyable(),
                            ]),
                    ]),

                Section::make('Contact Information')
                    ->icon(Heroicon::OutlinedPhone)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('primary_phone')
                            ->label('Primary Phone')
                            ->copyable(),
                        TextEntry::make('secondary_phone')
                            ->label('Secondary Phone')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->placeholder('—'),
                        TextEntry::make('physical_address')
                            ->columnSpanFull()
                            ->placeholder('—'),
                        TextEntry::make('district')
                            ->placeholder('—'),
                        TextEntry::make('village')
                            ->placeholder('—'),
                        TextEntry::make('postal_address')
                            ->placeholder('—'),
                    ]),

                Section::make('Employment & Income')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('occupation')
                            ->placeholder('—'),
                        TextEntry::make('employer_name')
                            ->placeholder('—'),
                        TextEntry::make('monthly_income_range')
                            ->label('Income Range')
                            ->placeholder('—'),
                    ]),

                Section::make('Next of Kin')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('nok_name')
                            ->label('Name')
                            ->placeholder('—'),
                        TextEntry::make('nok_relationship')
                            ->label('Relationship')
                            ->placeholder('—'),
                        TextEntry::make('nok_contact')
                            ->label('Contact')
                            ->placeholder('—'),
                    ]),

                Section::make('KYC & Compliance')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('kyc_score')
                            ->label('KYC Score')
                            ->suffix('%')
                            ->badge()
                            ->color(fn (int $state, Member $record): string => $state >= $record->kyc_threshold ? 'success' : 'danger'),
                        TextEntry::make('kyc_threshold')
                            ->label('Required Threshold')
                            ->suffix('%'),
                        TextEntry::make('member_category')
                            ->label('Category')
                            ->badge(),
                        TextEntry::make('referral_source')
                            ->label('Referral Source')
                            ->placeholder('—'),
                        TextEntry::make('branch_code')
                            ->label('Branch')
                            ->placeholder('—'),
                    ]),

                Section::make('Share Capital')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('shares.shares_held')
                            ->label('Shares Held')
                            ->default(0),
                        TextEntry::make('shares.par_value')
                            ->label('Par Value')
                            ->money('UGX')
                            ->default(0),
                        TextEntry::make('shares.total_value')
                            ->label('Total Value')
                            ->money('UGX')
                            ->default(0),
                        TextEntry::make('shares.percentage_of_total')
                            ->label('Ownership %')
                            ->suffix('%')
                            ->default('0.0000'),
                    ]),

                Section::make('Lifecycle History')
                    ->icon(Heroicon::OutlinedClock)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('stateHistory')
                            ->label('')
                            ->schema([
                                TextEntry::make('from_state')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('to_state')
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('reason_code')
                                    ->placeholder('—'),
                                TextEntry::make('notes')
                                    ->placeholder('—'),
                                TextEntry::make('transitioned_at')
                                    ->dateTime(),
                            ])
                            ->columns(5),
                    ]),

                Section::make('KYC Documents')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('documents')
                            ->label('')
                            ->schema([
                                TextEntry::make('document_type')
                                    ->badge(),
                                TextEntry::make('verification_status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'verified' => 'success',
                                        'pending' => 'warning',
                                        'rejected' => 'danger',
                                        'expired' => 'gray',
                                        default => 'gray',
                                    }),
                                TextEntry::make('upload_date')
                                    ->date(),
                                TextEntry::make('expiry_date')
                                    ->date()
                                    ->placeholder('N/A'),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Audit Trail')
                    ->icon(Heroicon::OutlinedFingerPrint)
                    ->columns(4)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('registered_by')
                            ->placeholder('System'),
                        TextEntry::make('approved_by')
                            ->placeholder('—'),
                        TextEntry::make('approved_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Registered At')
                            ->dateTime(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'view' => ViewMember::route('/{record}'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}
