<?php

use App\Services\DokuStatus;

it('PAID selalu menang atas order status apapun (termasuk ORDER_EXPIRED)', function (string $paidStatus) {
    expect(DokuStatus::outcome($paidStatus, 'ORDER_EXPIRED'))->toBe(DokuStatus::PAID)
        ->and(DokuStatus::outcome($paidStatus, 'ORDER_GENERATED'))->toBe(DokuStatus::PAID)
        ->and(DokuStatus::outcome($paidStatus, 'ORDER_RECOVERED'))->toBe(DokuStatus::PAID)
        ->and(DokuStatus::outcome($paidStatus, null))->toBe(DokuStatus::PAID);
})->with([
    'SUCCESS',
    'PAID',
    'COMPLETED',
    'SETTLED',
    'SETTLEMENT',
    'CAPTURED',
    ' success ', // normalisasi: case + spasi
]);

it('PENDING + ORDER_EXPIRED berarti expired (checkout kedaluwarsa tanpa channel)', function () {
    expect(DokuStatus::outcome('PENDING', 'ORDER_EXPIRED'))->toBe(DokuStatus::EXPIRED);
});

it('transaction EXPIRED + ORDER_EXPIRED berarti expired', function () {
    expect(DokuStatus::outcome('EXPIRED', 'ORDER_EXPIRED'))->toBe(DokuStatus::EXPIRED);
});

it('transaction EXPIRED tanpa ORDER_EXPIRED berarti expired (VA/channel kedaluwarsa)', function () {
    expect(DokuStatus::outcome('EXPIRED', 'ORDER_GENERATED'))->toBe(DokuStatus::EXPIRED)
        ->and(DokuStatus::outcome('EXPIRED', null))->toBe(DokuStatus::EXPIRED);
});

it('ORDER_RECOVERED selalu ditahan kecuali transaction sudah PAID', function () {
    expect(DokuStatus::outcome('EXPIRED', 'ORDER_RECOVERED'))->toBe(DokuStatus::HOLD)
        ->and(DokuStatus::outcome('PENDING', 'ORDER_RECOVERED'))->toBe(DokuStatus::HOLD)
        ->and(DokuStatus::outcome('FAILED', 'ORDER_RECOVERED'))->toBe(DokuStatus::HOLD)
        ->and(DokuStatus::outcome('SUCCESS', 'ORDER_RECOVERED'))->toBe(DokuStatus::PAID);
});

it('status belum final ditahan (tidak dibatalkan)', function (?string $transaction, ?string $order) {
    expect(DokuStatus::outcome($transaction, $order))->toBe(DokuStatus::HOLD);
})->with([
    'PENDING + ORDER_GENERATED' => ['PENDING', 'ORDER_GENERATED'],
    'PENDING + null' => ['PENDING', null],
    'FAILED masih bisa ganti channel' => ['FAILED', 'ORDER_GENERATED'],
    'TIMEOUT bukan final' => ['TIMEOUT', 'ORDER_GENERATED'],
    'REDIRECT bukan final' => ['REDIRECT', 'ORDER_GENERATED'],
    'kosong/404' => [null, null],
    'string kosong' => ['', ''],
]);
