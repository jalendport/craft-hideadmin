<?php
/**
 * Hide Admin plugin for Craft CMS 3.x
 *
 * Hide admin accounts from non-admin accounts
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\hideadmin;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\controllers\UsersController;
use craft\elements\User;
use craft\events\RegisterElementSourcesEvent;
use craft\events\RegisterUserActionsEvent;

use yii\base\Event;
use yii\web\ForbiddenHttpException;

/**
 * Class HideAdmin
 *
 * @author    Jalen Davenport
 * @package   HideAdmin
 * @since     1.0.0
 *
 */
class HideAdmin extends Plugin
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        Event::on(
            User::class,
            Element::EVENT_REGISTER_SOURCES,
            function(RegisterElementSourcesEvent $event) {
                $currentUser = Craft::$app->getUser()->getIdentity();

				if (!$currentUser->admin) {
					// Remove "Admins" source
					$event->sources = array_filter($event->sources, function($source) {
						$isHeading = array_key_exists('heading', $source);
						return $isHeading || ($source['key'] != 'admins');
					});

					// Remove admin users from all sources
					foreach ($event->sources as $key => $source) {
						$isHeading = array_key_exists('heading', $source);
						if (!$isHeading)
						{
							$event->sources[$key]['criteria']['admin'] = false;
						}
					}
				}
            }
        );
    }
}
