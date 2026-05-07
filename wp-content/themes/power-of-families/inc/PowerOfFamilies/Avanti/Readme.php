<?php

namespace PowerOfFamilies\Avanti;

/**
 * Create a readme page for the admin side of the site
 */
class Readme
{

    public function __construct()
    {
        add_action('admin_menu', $this->registerCustomPage(...));
    }

    public function registerCustomPage(): void
    {
        add_menu_page('About The Template', 'About', 'manage_options', 'readme', $this->customPageOutput(...),
            'dashicons-info', NULL);
    }

    public function customPageOutput(): void
    {
        ob_start();
        ?>

        <div class="about-wrap wrap">

            <h1>Power of Families Custom Theme</h1>

            <p>
                If you're new to wordpress, go to the dashboard and click the "help" link at the top-right of the page.
                Wordpress provides a number of tutorials.
            </p>

            <hr>

            <h2>Keep Wordpress Up-To-Date!!!</h2>

            <p>
                <strong>ALWAYS keep Wordpress, themes, and plugins up-to-date.</strong> If Wordpress is notifying you of
                an
                update, follow through and do it right away.
                Failure to do so may leave this site vulnerable to attacks!
            </p>

            <hr>

            <h2>Plugins</h2>

            <p>
                A note about plugins: There are a lot of good plugins out there. But if you're going to install a
                plugin,
                remember there can be
                consequences. Plugins can go out of date or introduce errors or security threats. Consider the following
                when
                adding plugins:
            </p>

            <ul>
                <li>Plugins slow down the site. So consider the cost-benefit when adding a plugin.</li>
                <li>When reviewing a plugin on the Wordpress site or within admin, look at the details. Make sure the
                    plugin has
                    been recently
                    updated (meaning it is kept up-to-date). Avoid plugins that are more than several months
                    out-of-date.
                </li>
                <li>Choose plugins that have good reviews, and are more popular.</li>
            </ul>

            <p>
                Don't hesitate to ask the author if you have questions about a plugin or are looking for something to
                solve a
                problem.
                It's possible I could create your functionality with a bit of custom coding in our theme.
            </p>

            <hr>

        </div>

        <?php
        ob_end_flush();
    }

}
