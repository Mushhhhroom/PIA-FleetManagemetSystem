<?php

passthru('php "' . dirname(__DIR__) . '/spark" pia:test', $returnVar);
exit($returnVar);
