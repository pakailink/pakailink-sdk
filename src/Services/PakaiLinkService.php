<?php

namespace PakaiLink\Services;

use Illuminate\Support\Str;

class PakaiLinkService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
    ) {}

    /**
     * Get account balance.
     */
    public function getBalance(): array
    {
        return $this->client->post(
            config('pakailink.endpoints.balance.inquiry', '/api/v1.0/balance-inquiry'),
            [
                'partnerReferenceNo' => $this->generateReferenceNo(),
            ]);
    }

    /**
     * Get balance history.
     */
    public function getBalanceHistory(
        string $startDate,
        string $endDate,
        int $page = 1,
        int $limit = 100
    ): array {
        return $this->client->post(
            config('pakailink.endpoints.balance.history', '/api/v1.0/balance-history'),
            [
                'partnerReferenceNo' => $this->generateReferenceNo(),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'page' => $page,
                'limit' => $limit,
            ]);
    }

    /**
     * Generate unique reference number.
     */
    protected function generateReferenceNo(): string
    {
        return 'BPG-'.now()->format('YmdHis').'-'.strtoupper(Str::random(6));
    }

    /**
     * Get HTTP client instance.
     */
    public function getClient(): PakaiLinkHttpClient
    {
        return $this->client;
    }
}
