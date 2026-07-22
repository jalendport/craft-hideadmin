<?php
/**
 * Hide Admin plugin for Craft CMS 4.x
 *
 * Hide admin accounts from non-admin accounts
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\hideadmin\services;

use Craft;
use craft\base\Component;
use craft\elements\User;

/**
 * Decides which users must be hidden from the current viewer, and the request
 * contexts in which that hiding applies.
 *
 * The decision logic is kept free of Craft calls — see [[hides()]] and
 * [[filterSources()]] — so it can be unit-tested without bootstrapping the CMS.
 *
 * @author    Jalen Davenport
 * @package   HideAdmin
 * @since     2.0.0
 */
class HiddenUsers extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Whether admin users should be hidden in the current request: a
     * control-panel web request made by an authenticated non-admin.
     *
     * @return bool
     */
    public function shouldFilter(): bool
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest() || !$request->getIsCpRequest()) {
            return false;
        }

        /** @var \craft\web\Application $app */
        $app = Craft::$app;
        $identity = $app->getUser()->getIdentity();

        return $identity !== null && !$identity->admin;
    }

    /**
     * Whether a target user must be hidden from a given viewer.
     *
     * @param User $target the user being viewed, saved, or deleted
     * @param User $viewer the user performing the action
     * @return bool
     */
    public function isHidden(User $target, User $viewer): bool
    {
        return $this->hides($target->admin, $viewer->admin, $viewer->id === $target->id);
    }

    /**
     * The visibility rule: a non-admin viewer may not see admin users, but
     * every viewer may always see themselves.
     *
     * @param bool $targetIsAdmin whether the target user is an admin
     * @param bool $viewerIsAdmin whether the viewer is an admin
     * @param bool $isSelf whether the viewer and target are the same user
     * @return bool
     */
    public function hides(bool $targetIsAdmin, bool $viewerIsAdmin, bool $isSelf): bool
    {
        if ($viewerIsAdmin || $isSelf) {
            return false;
        }

        return $targetIsAdmin;
    }

    /**
     * Removes the “Admins” source and hides admin users from the remaining
     * sources on the Users element index.
     *
     * Keys are re-sequenced with `array_values()` so a gap left by the removed
     * source can’t make the list serialize as a JSON object.
     *
     * @param array $sources the registered element index sources
     * @return array
     */
    public function filterSources(array $sources): array
    {
        $sources = array_filter($sources, static function(array $source): bool {
            return array_key_exists('heading', $source) || ($source['key'] ?? null) !== 'admins';
        });

        foreach ($sources as &$source) {
            if (!array_key_exists('heading', $source)) {
                $source['criteria']['admin'] = false;
            }
        }
        unset($source);

        return array_values($sources);
    }
}
