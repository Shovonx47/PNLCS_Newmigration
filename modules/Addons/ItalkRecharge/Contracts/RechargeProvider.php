<?php

namespace Modules\Addons\ItalkRecharge\Contracts;

/**
 * One way of topping up an IPTSP account: the iTalk/ICC browser flow, the
 * Ranksitt REST API or the WebVoice REST API.
 *
 * Every driver answers the same three questions so the recharge service never
 * needs to know which provider it is talking to: can I sign in, which internal
 * account id belongs to this PIN, and did the top-up succeed.
 */
interface RechargeProvider
{
    /** The provider's api_type ("italk", "ranksitt", "webvoice"). */
    public function key(): string;

    /** @return array{ok: bool, message: string} */
    public function login(): array;

    /** @return array{ok: bool, id: ?string, message: string} */
    public function findAccount(string $pin): array;

    /** @return array{ok: bool, message: string, raw: mixed} */
    public function recharge(string $accountId, string $pin, float $amount): array;
}
