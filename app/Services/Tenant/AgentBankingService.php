<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Agent;
use App\Models\Tenant\AgentTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Agent banking service (FR-CH-030–031).
 */
class AgentBankingService
{
    /**
     * Register a new agent.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerAgent(array $data): Agent
    {
        $code = 'AG-'.Str::upper(Str::random(6));

        return Agent::create(array_merge($data, [
            'agent_code' => $code,
            'status' => Agent::STATUS_ACTIVE,
        ]));
    }

    /**
     * Record a member transaction through an agent.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordTransaction(int $agentId, array $data): AgentTransaction
    {
        $agent = Agent::findOrFail($agentId);

        $amount = (float) $data['amount'];
        $type = $data['transaction_type'];
        $floatBefore = (float) $agent->float_balance;
        $commission = 0.0;
        $floatAfter = $floatBefore;

        if ($type === AgentTransaction::TYPE_DEPOSIT) {
            $floatAfter = $floatBefore + $amount;
            $commission = $agent->computeCommission($amount);
        } elseif ($type === AgentTransaction::TYPE_WITHDRAWAL) {
            if (! $agent->hasFloatCapacity($amount)) {
                throw new \RuntimeException('Insufficient float balance.');
            }
            $floatAfter = $floatBefore - $amount;
            $commission = $agent->computeCommission($amount);
        } elseif ($type === AgentTransaction::TYPE_FLOAT_TOP_UP) {
            $floatAfter = $floatBefore + $amount;
        }

        $ref = 'AT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        $txn = AgentTransaction::create(array_merge($data, [
            'transaction_ref' => $ref,
            'agent_id' => $agentId,
            'amount' => $amount,
            'commission_amount' => $commission,
            'float_before' => $floatBefore,
            'float_after' => $floatAfter,
            'status' => 'completed',
        ]));

        $agent->update([
            'float_balance' => $floatAfter,
            'total_commission_earned' => (float) $agent->total_commission_earned + $commission,
        ]);

        return $txn;
    }

    /**
     * Get agent performance summary.
     *
     * @return Collection<int, array{agent_code: string, agent_name: string, float_balance: float, total_transactions: int, total_commission: float}>
     */
    public function getAgentPerformance(): Collection
    {
        return Agent::query()
            ->active()
            ->get()
            ->map(fn (Agent $agent): array => [
                'agent_code' => $agent->agent_code,
                'agent_name' => $agent->agent_name,
                'float_balance' => (float) $agent->float_balance,
                'total_transactions' => $agent->agentTransactions()->count(),
                'total_commission' => (float) $agent->total_commission_earned,
            ]);
    }
}
