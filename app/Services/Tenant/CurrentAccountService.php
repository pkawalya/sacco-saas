<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Card;
use App\Models\Tenant\CurrentAccount;
use App\Models\Tenant\FxRate;

/**
 * Current accounts, FX, and card management service.
 */
class CurrentAccountService
{
    /**
     * Open a current account.
     *
     * @param  array<string, mixed>  $data
     */
    public function openAccount(array $data): CurrentAccount
    {
        $prefix = match ($data['account_type'] ?? 'individual') {
            'business' => 'BIZ',
            'corporate' => 'CORP',
            default => 'CA',
        };

        $accountNumber = $prefix.'-'.now()->format('Y').'-'.str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT);

        return CurrentAccount::create(array_merge($data, [
            'account_number' => $accountNumber,
            'status' => 'active',
        ]));
    }

    /**
     * Get FX conversion quote.
     *
     * @return array{from: string, to: string, amount: float, converted: float, rate: float, direction: string}|null
     */
    public function getConversionQuote(
        float $amount,
        string $fromCurrency,
        string $toCurrency,
        string $direction = 'sell'
    ): ?array {
        $rate = FxRate::getLatest($fromCurrency === 'UGX' ? $toCurrency : $fromCurrency);

        if (! $rate) {
            return null;
        }

        if ($fromCurrency === 'UGX') {
            $converted = round($amount / (float) $rate->{$direction.'_rate'}, 2);
        } else {
            $converted = $rate->convert($amount, $direction);
        }

        return [
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'amount' => $amount,
            'converted' => $converted,
            'rate' => (float) $rate->{$direction.'_rate'},
            'direction' => $direction,
        ];
    }

    /**
     * Issue a card.
     *
     * @param  array<string, mixed>  $data
     */
    public function issueCard(array $data): Card
    {
        $last4 = str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $cardNum = '4'.str_pad((string) rand(1, 999999999999999), 15, '0', STR_PAD_LEFT);

        return Card::create(array_merge($data, [
            'card_number' => $cardNum,
            'masked_pan' => 'XXXX-XXXX-XXXX-'.substr($cardNum, -4),
            'status' => 'active',
        ]));
    }

    /**
     * Block a card.
     */
    public function blockCard(int $cardId, string $reason): Card
    {
        $card = Card::findOrFail($cardId);
        $card->block($reason);

        return $card->fresh();
    }
}
