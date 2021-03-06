<?php
/**
 * ownCloud - owncollab_calendar
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bogdan <mail@example.com>
 * @copyright Bogdan 2016
 */

namespace OCA\Owncollab_Calendar\AppInfo;

use OCP\AppFramework\App;

if(\OCP\App::isEnabled('calendar') && \OC_App::isEnabled('owncollab_chart')) {
    $app = new App('owncollab_calendar');
    $container = $app->getContainer();
}

/*
$container->query('OCP\INavigationManager')->add(function () use ($container) {
	$urlGenerator = $container->query('OCP\IURLGenerator');
	$l10n = $container->query('OCP\IL10N');
	return [
		// the string under which your app will be referenced in owncloud
		'id' => 'owncollab_calendar',

		// sorting weight for the navigation. The higher the number, the higher
		// will it be listed in the navigation
		'order' => 10,

		// the route that will be shown on startup
		'href' => $urlGenerator->linkToRoute('owncollab_calendar.page.index'),

		// the icon that will be shown in the navigation
		// this file needs to exist in img/
		'icon' => $urlGenerator->imagePath('owncollab_calendar', 'app.svg'),

		// the title of your application. This will be used in the
		// navigation or on the settings page of your app
		'name' => $l10n->t('Owncollab Calendar'),
	];
});
*/