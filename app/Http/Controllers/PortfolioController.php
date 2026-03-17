<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\StoreTransactionRequest;
use App\Contracts\EloquentTransactionServiceInterface;
use App\Contracts\EloquentTickerServiceInterface;
use App\Contracts\SettingServiceInterface;
use App\Contracts\EloquentUserServiceInterface;
use App\Exceptions\Domain\CurrencyNotSetException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

final class PortfolioController extends Controller
{
    public function __construct(
        private readonly EloquentTransactionServiceInterface $transactionService,
        private readonly EloquentTickerServiceInterface $tickerService,
        private readonly SettingServiceInterface $settingService,
        private readonly EloquentUserServiceInterface $userService
    ) {}

    public function telegramLink(): JsonResponse
    {
        $token = $this->userService->generateLinkingToken(Auth::id());
        $botName = config('services.telegram.bot_name');

        if (!$botName) {
            return response()->json(['error' => 'Telegram bot not configured'], 500);
        }

        $url = "https://t.me/{$botName}?start={$token}";

        return response()->json(['url' => $url]);
    }

    public function index(): AnonymousResourceCollection
    {
        $transactions = $this->transactionService->getHistory(Auth::id(), 20);

        return TransactionResource::collection($transactions);
    }

    public function store(StoreTransactionRequest $request): TransactionResource
    {
        if (!$this->settingService->get('currency_code')) {
            throw new CurrencyNotSetException();
        }

        $validated = $request->validated();

        $ticker = $this->tickerService->getOrCreateTicker($validated['symbol']);

        $transaction = $this->transactionService->createTransaction(
            Auth::id(),
            $ticker->id,
            $validated['type'],
            (float) $validated['quantity'],
            (float) $validated['price'],
            $validated['date'] ?? null
        );

        return new TransactionResource($transaction);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $deleted = $this->transactionService->deleteTransaction($transaction, Auth::id());

        if (!$deleted) {
            return response()->json(['message' => 'Unauthorized or failed'], 403);
        }

        return response()->json(['message' => 'Transaction deleted']);
    }
}
