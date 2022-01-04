<?php
namespace Ripen\CatsyIntegration\Cron;

class SyncAll
{
    /**
     * @var \Ripen\CatsyIntegration\Model\Sync
     */
    protected $sync;

    /**
     * SyncAll constructor.
     * @param \Ripen\CatsyIntegration\Model\Sync $sync
     */
    public function __construct(
        \Ripen\CatsyIntegration\Model\Sync $sync
    ) {
        $this->sync = $sync;
    }

    public function execute()
    {
        $this->sync->run();
    }
}
