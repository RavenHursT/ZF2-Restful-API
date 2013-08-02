<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace EventLogger\Service;

use DateTime;
use ErrorException;
use Traversable;
use Zend\Log\Logger;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\SplPriorityQueue;
use Zend\Log\Exception;

/**
 * Logging messages with a stack of backends
 */
class EventLogger extends Logger
{

    /**
     * Add a message as a log entry
     *
     * @param  int $priority
     * @param  mixed $message
     * @param  array|Traversable $extra
     * @return Logger
     * @throws Exception\InvalidArgumentException if message can't be cast to string
     * @throws Exception\InvalidArgumentException if extra can't be iterated over
     * @throws Exception\RuntimeException if no log writer specified
     */
    public function log($priority, $message, $extra = array()){
        if (!is_int($priority) || ($priority<0) || ($priority>=count($this->priorities))) {
            throw new Exception\InvalidArgumentException(sprintf(
                '$priority must be an integer > 0 and < %d; received %s',
                count($this->priorities),
                var_export($priority, 1)
            ));
        }
        if (is_object($message) && !method_exists($message, '__toString')) {
            throw new Exception\InvalidArgumentException(
                '$message must implement magic __toString() method'
            );
        }

        if (!is_array($extra) && !$extra instanceof Traversable) {
            throw new Exception\InvalidArgumentException(
                '$extra must be an array or implement Traversable'
            );
        } elseif ($extra instanceof Traversable) {
            $extra = ArrayUtils::iteratorToArray($extra);
        }

        if ($this->writers->count() === 0) {
            throw new Exception\RuntimeException('No log writer specified');
        }

        $timestamp = new DateTime();

        if (is_array($message)) {
            $message = var_export($message, true);
        }

		$callerTrace = $this->_getCallerBacktrace();

        $event = array(
            'timestamp'    => $timestamp,
            'priority'     => (int) $priority,
            'priorityName' => $this->priorities[$priority],
            'message'      => (string) $message,
            'extra'        => $extra,
        );

		if($callerTrace){
			$event = array_merge($event, $callerTrace);
		}

		if(isset($_SERVER['UNIQUE_ID'])){
			$event['requestFingerprint'] = $_SERVER['UNIQUE_ID'];
		}

        foreach ($this->processors->toArray() as $processor) {
            $event = $processor->process($event);
        }

        foreach ($this->writers->toArray() as $writer) {
            $writer->write($event);
        }

        return $this;
    }

	private function _getCallerBacktrace(){
		$backtrace = debug_backtrace();
		$callerIndex = NULL;
		foreach($backtrace as $index => $trace){
			if(
				isset($trace['class']) &&
				!in_array($trace['class'], array(__CLASS__, get_parent_class($this)))
			){
//				print_r($trace);exit;
				return $trace;
			}
		}
		return NULL;
	}
}
