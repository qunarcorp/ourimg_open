<?php

/**
 * Class QJob_TestJobFalse
 */

class QJob_TestJobFalse implements \QBus_ShouldQueue
{
    use \QBus_Dispatchable;

    public function handle() : bool
    {
        return false;
    }
}