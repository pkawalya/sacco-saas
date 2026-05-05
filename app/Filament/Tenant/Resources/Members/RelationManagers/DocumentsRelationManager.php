<?php

namespace App\Filament\Tenant\Resources\Members\RelationManagers;

use App\Models\Tenant\MemberDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type')
                    ->label('Document Type')
                    ->options(MemberDocument::TYPES)
                    ->required()
                    ->searchable(),

                Select::make('verification_status')
                    ->label('Verification Status')
                    ->options([
                        MemberDocument::STATUS_PENDING => 'Pending',
                        MemberDocument::STATUS_VERIFIED => 'Verified',
                        MemberDocument::STATUS_REJECTED => 'Rejected',
                        MemberDocument::STATUS_EXPIRED => 'Expired',
                    ])
                    ->default(MemberDocument::STATUS_PENDING)
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->disk('public')
                    ->directory('kyc-documents')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120)
                    ->columnSpanFull(),

                DatePicker::make('upload_date')
                    ->label('Upload Date')
                    ->default(now())
                    ->required(),

                DatePicker::make('expiry_date')
                    ->label('Expiry Date'),

                Textarea::make('notes')
                    ->label('Notes / Rejection Reason')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_type')
            ->columns([
                TextColumn::make('document_type')
                    ->label('Document Type')
                    ->formatStateUsing(fn (string $state): string => MemberDocument::TYPES[$state] ?? $state)
                    ->badge()
                    ->searchable(),

                TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('upload_date')
                    ->label('Upload Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->placeholder('N/A')
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('verified_at')
                    ->label('Verified At')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('upload_date', 'desc')
            ->filters([
                SelectFilter::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ]),

                SelectFilter::make('document_type')
                    ->options(MemberDocument::TYPES),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon(Heroicon::OutlinedDocumentPlus),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
