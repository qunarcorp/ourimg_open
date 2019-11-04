<?php

/**
 * Class QJob_TestJobTrue
 */

class QJob_TestJobTrue implements \QBus_ShouldQueue
{
    use \QBus_Dispatchable;

    public function handle() : bool
    {
        return true;
    }
}