<?php

namespace Watish\WatishWEB\Process;

use Cron\CronExpression;
use Swoole\Coroutine;
use Watish\Components\Attribute\Crontab;
use Watish\Components\Constructor\ClassLoaderConstructor;
use Watish\Components\Utils\AttributeLoader\AttributeLoader;
use Watish\Components\Utils\Logger;

class CrontabProcess implements ProcessInterface
{
    public function execute(\Swoole\Process $process): void
    {
        $classLoader = ClassLoaderConstructor::getClassLoader("crontab");
        $attributeLoader = new AttributeLoader($classLoader->getClasses());
        $attributes = $attributeLoader->getClassAttributes(Crontab::class);

        $crontab = [];
        foreach ($attributes as $class => $item) {
            if ($item["count"] > 0) {
                $cron_rule = $item["attributes"][0]["params"][0];
                Logger::debug($cron_rule, "Crontab");
                $cron = new CronExpression($cron_rule);
                $crontab[] = [
                    "rule" => $cron_rule,
                    "cron" => $cron,
                    "callback" => [new $class(),"execute"]
                ];
            }
        }
        Logger::debug("Crontab Process Started", "Crontab");
        $cron_id = 0;
        foreach ($crontab as $item) {
            $cron_id++;
            $cron = $item["cron"];
            $rule = $item["rule"];
            $callback = $item["callback"];
            Coroutine::create(function () use ($cron_id, $cron, $rule, $callback) {
                Logger::debug($rule, "Crontab{$cron_id}");
                while (1) {
                    if ($cron->isDue()) {
                        call_user_func_array($callback, []);
                    }
                    Coroutine::sleep(1);
                }
            });
        }
    }
}
