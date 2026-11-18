<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Util;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Symfony 2.8 から 5.4 までの EventDispatcher に、同じ書き方でイベントを発行する。
 *
 * Symfony 4.3 で dispatch() の引数の順番が dispatch($eventName, $event) から
 * dispatch($event, $eventName) に変わり、Symfony 5.0 で旧来の呼び方が削除された。
 */
class EventDispatcherUtil
{
    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $eventName
     * @param object|null $event 省略時は GenericEvent (全バージョンに存在する)
     *
     * @return object 発行したイベント
     */
    public static function dispatch(EventDispatcherInterface $eventDispatcher, $eventName, $event = null)
    {
        if ($event === null) {
            $event = new GenericEvent();
        }

        // Symfony 4.3 以降の EventDispatcherInterface は symfony/event-dispatcher-contracts のものを継承している
        $contract = 'Symfony\Contracts\EventDispatcher\EventDispatcherInterface';
        if (interface_exists($contract) && $eventDispatcher instanceof $contract) {
            $eventDispatcher->dispatch($event, $eventName);
        } else {
            $eventDispatcher->dispatch($eventName, $event);
        }

        return $event;
    }
}
