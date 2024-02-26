<?php

use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractExtension extends \Latte\Extension implements ServiceSubscriberInterface
{


	 public static function getSubscribedServices() : array {
		 // TODO: Implement getSubscribedServices() method.
	 }
 }