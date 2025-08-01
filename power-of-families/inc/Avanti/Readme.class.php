<?php

namespace Avanti;

/**
 * Create a readme page for the admin side of the site
 */
class Readme
{

    function __construct()
    {
        add_action('admin_menu', array($this, 'registerCustomPage'));
    }

    function registerCustomPage()
    {
        add_menu_page('About The Template', 'About', 'manage_options', 'readme', array($this, 'customPageOutput'),
            'dashicons-info', NULL);
    }

    function customPageOutput()
    {
        ob_start();
        ?>

        <div class="about-wrap wrap">

            <style type="text/css" scoped>
                code {
                    display: inline-block;
                }

                .about-wrap h2 {
                    text-align: left;
                }

                .shortcodes {
                    background: #e7e7e7;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 30px;
                }

                .shortcodes li {
                    padding: 15px;
                    border-bottom: 1px solid #878787;
                }

                .shortcodes li:first-child {
                    border-top: 1px solid #878787;
                }

                .shortcodes h3 {
                    margin-top: 0;
                }

                table.shortcode {
                    border-spacing: 0px;
                    width: 100%;
                }

                table.shortcode td {
                    vertical-align: top;
                    text-align: left;
                    padding: 6px;
                    border-top: 1px solid #c0c0c0;
                }

                table.shortcode ul {
                    margin: 0 0 15px;
                }
            </style>

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

<!--            <h2>Product Solutions</h2>-->
<!---->
<!--            <p>-->
<!--                Product solutions are grouped by "Solution Category". The slug values-->
<!--                for these categories are tied directly with the icons used to display them-->
<!--                with the [product_solutions] shortcode. These slugs must be:-->
<!--            </p>-->
<!---->
<!--            <ul>-->
<!--                <li>local-print-directories</li>-->
<!--                <li>local-search</li>-->
<!--                <li>marketing-platform</li>-->
<!--                <li>online-advertising</li>-->
<!--                <li>social-media</li>-->
<!--                <li>websites</li>-->
<!--            </ul>-->
<!---->
<!--            <hr>-->
<!---->
<!--            <h2>Education, News &amp; Awards (Posts)</h2>-->
<!---->
<!--            <p>-->
<!--                Post categories must be configured as follows:-->
<!--            </p>-->
<!---->
<!--            <ul>-->
<!--                <li>Education (slug: <code>education</code>)</li>-->
<!--                <li>- (Any number of categories here)</li>-->
<!--                <li>News &amp; Awards (any slug)</li>-->
<!--                <li>- In the News (slug: <code>news</code>)</li>-->
<!--                <li>- Awards &amp; Recognition (slug: <code>awards</code>)</li>-->
<!--            </ul>-->
<!---->
<!--            <p><em>* This theme does not support subcategories under <code>In the News</code> or <code>Awards &amp;-->
<!--                        Recognition</code></em></p>-->
<!---->
<!--            <h4>Menu items for Education and News &amp; Awards</h4>-->
<!--            <p>-->
<!--                Add a category menu item for Education to the main menu.-->
<!--            </p>-->
<!--            <p>-->
<!--                Add a category menu item for News &amp; Awards to the footer.-->
<!--            </p>-->
<!---->
<!--            <h4>Menu items for Education and News &amp; Awards</h4>-->
<!--            <p>-->
<!--                There is a widget area called "Education Footer" where you can add-->
<!--                content such as a list of people who contribute. You can add the-->
<!--                "Black Studio TinyMCE Widget" to the widget area to add formatting.-->
<!--            </p>-->
<!---->
<!--            <hr>-->
<!---->
<!--            <h2>Subheader Menu</h2>-->
<!---->
<!--            <p>-->
<!--                A subheader menu can be added to any page in the page edit screen (right-hand side).-->
<!--                Menu items can optionally support icons through the use of hard-coded classes. You must specify these-->
<!--                classes-->
<!--                when editing menu items in Appearance &gt; Menus. New icons must be added by a developer.-->
<!--                The following classes are supported:-->
<!--            </p>-->
<!---->
<!--            <ul>-->
<!--                <li><code>resellers</code></li>-->
<!--                <li><code>telecom</code></li>-->
<!--                <li><code>franchises</code></li>-->
<!--                <li><code>partner-overview</code></li>-->
<!--                <li><code>local-print</code></li>-->
<!--                <li><code>local-search</code></li>-->
<!--                <li><code>marketing-platform</code></li>-->
<!--                <li><code>online-advertising</code></li>-->
<!--                <li><code>social-media</code></li>-->
<!--                <li><code>websites</code></li>-->
<!--            </ul>-->
<!---->
<!--            <p>-->
<!--                <em>*This menu only supports a flat structure</em>-->
<!--            </p>-->
<!---->
<!--            <hr>-->
<!---->
<!--            <h2>Menus</h2>-->
<!---->
<!--            <p>-->
<!--                This theme supports multiple menus. The main menu supports a dropdown-->
<!--                only for the menu item with the class <code>solutions</code>.-->
<!--                This solutions menu item has two submenus for Industry and Product. The same-->
<!--                menus here can be used in the footer.-->
<!--            </p>-->
<!---->
<!--            <hr>-->
<!---->
<!--            <h2>Shortcodes</h2>-->
<!---->
<!--            <p>-->
<!--                Shortcodes provide additional functionality to normal content. There are a few shortcodes-->
<!--                created for this theme which do things like provide a list of records, etc.-->
<!--            </p>-->
<!---->
<!--            <p>-->
<!--                Use the guide here to include shortcodes. Buttons have also been added to the "text" view of the-->
<!--                wordpress-->
<!--                content editor for generating shortcodes for you.-->
<!--            </p>-->
<!---->
<!--            <ul class="shortcodes">-->
<!--                <li>-->
<!--                    <h3>Page Section</h3>-->
<!--                    <p>-->
<!--                        <code>[page_section ] //some content [/page_section]</code> Creates a section of content that is-->
<!--                        contained-->
<!--                        within a centered container. White or no background.-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        <code>[page_section color="gray" full_width="false" flush="false"] //some content-->
<!--                            [/page_section]</code>-->
<!--                        Creates a section of content that is gray and spans the width of the page, but the content is-->
<!--                        still-->
<!--                        contained.-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        <code>[page_section color="black" full_width="true" flush="true" pattern="2"] //some content-->
<!--                            [/page_section]</code> Creates a section of content that is black, full width content, and-->
<!--                        no padding-->
<!--                        inside, and renders pattern 2 on the sides.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>color</td>-->
<!--                            <td>-->
<!--                                Optional. Background color of the section. Can be <code>gray | charcoal | black |-->
<!--                                    darkcharcoal</code>.-->
<!--                                Defaults to transparent.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>full_width</td>-->
<!--                            <td>-->
<!--                                Optional. If <code>true</code> the content will span the full width of the page.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>flush</td>-->
<!--                            <td>-->
<!--                                Optional. If <code>true</code> there will be no padding in this section.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>pattern</td>-->
<!--                            <td>-->
<!--                                Optional. Can be <code>1 | 2 | 3</code>. This will add a pattern on the sides of the-->
<!--                                section. The-->
<!--                                pattern number corresponds with different patterns and configurations.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Title</h3>-->
<!--                    <p>-->
<!--                        <code>[title]Title Here[/title]</code> Wraps text in the appropriate styling for a title.-->
<!--                    </p>-->
<!--                    <h4>No Arguments</h4>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Subtitle</h3>-->
<!--                    <p>-->
<!--                        <code>[subtitle]Title Here[/subtitle]</code> Wraps text in the appropriate styling for a smaller-->
<!--                        title.-->
<!--                    </p>-->
<!--                    <h4>No Arguments</h4>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Flexible Columns</h3>-->
<!--                    <p>-->
<!--                        <code>-->
<!--                            [flex_column_row]<br>-->
<!--                            [flex_column width="1/3" bg_color="gray" bg_image="http://url/of/image.jpg" padding="medium"-->
<!--                            margins="true" border="true"]<br>-->
<!--                            This is a one-third col with a gray background color, medium padding, a border, margin-->
<!--                            around it, and a background image<br>-->
<!--                            [/flex_column]<br>-->
<!--                            [flex_column width="2/3"]<br>-->
<!--                            This is a two-thirds col<br>-->
<!--                            [/flex_column]<br>-->
<!--                            [/flex_column_row]-->
<!--                        </code><br>-->
<!--                        Creates columns that have a flexible height - meaning all columns will have an equal height.-->
<!--                        Useful if you want some columns to have a full color background or background image.-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        <code>[flex_column_row] //all columns must be wrapped in here [/flex_column_row]</code> Wrap all-->
<!--                        columns-->
<!--                        from the same row in this.-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        <code>[flex_column width="1/2" bg_color="gray"] //some content [/flex_column]</code> Creates a-->
<!--                        column that-->
<!--                        fills 1/2 of the space, whose background is gray.-->
<!--                    </p>-->
<!--                    <h4>Arguments for <code>flex_column</code></h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>width</td>-->
<!--                            <td>-->
<!--                                Can be one of the following: <code>1/2 | 1/3 | 2/3 | 1/4 | 3/4</code>-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>bg_color</td>-->
<!--                            <td>-->
<!--                                Optional. Background color of the column. Can be <code>gray | charcoal | black |-->
<!--                                    white</code>. Defaults-->
<!--                                to-->
<!--                                transparent.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>bg_image</td>-->
<!--                            <td>-->
<!--                                Optional. Must be a full url (http...). This image will be used as a background image-->
<!--                                for the column.-->
<!--                                If the image is dark, you can make the text white if you set-->
<!--                                <code>bg_color="black"</code>.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>padding</td>-->
<!--                            <td>-->
<!--                                Optional. Can be one of the following: <code>large (default) | medium | small |-->
<!--                                    none</code>-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>margins</td>-->
<!--                            <td>-->
<!--                                Optional. Adds a margin around the column. Can be one of the following: <code>false-->
<!--                                    (default) | true</code>-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>border</td>-->
<!--                            <td>-->
<!--                                Optional. Adds a border around the column. Can be one of the following: <code>false-->
<!--                                    (default) | true</code>-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Flex Column Caption &amp; Flex Column Footer</h3>-->
<!--                    <p>-->
<!--                        <em>Both of these can only be used inside a <code>flex_column</code></em>-->
<!--                    <p>-->
<!--                        <code>[flex_column_caption]This is a flex-column caption that sits at the-->
<!--                            bottom[/flex_column_caption]</code>-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        <code>[flex_column_footer]Footer Title[/flex_column_footer]</code> Looks like a section title-->
<!--                        but smaller,-->
<!--                        and sits at the bottom - can be used with links inside.-->
<!--                    </p>-->
<!--                    <h4>No Arguments For Either</h4>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Product Solutions</h3>-->
<!--                    <p>-->
<!--                        <code>[product_solutions /]</code> Renders an interactive listing of solutions grouped by-->
<!--                        product solution category.-->
<!--                    </p>-->
<!--                    <h4>No Arguments</h4>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Industry Solutions</h3>-->
<!--                    <p>-->
<!--                        <code>[industry_solutions title="Your header content for this section" /]</code>-->
<!--                        Renders an interactive listing of industries-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>title</td>-->
<!--                            <td>-->
<!--                                The title text that shows at the top of the listing-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Challenges</h3>-->
<!--                    <p>-->
<!--                        <code>[challenges title="This is the title" ids="1,2,3,4" /]</code>-->
<!--                        Renders an interactive listing of challenges with their solutions-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>title</td>-->
<!--                            <td>-->
<!--                                The title text that shows at the top of the challenge navigation-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>ids</td>-->
<!--                            <td>-->
<!--                                Optional. A list of ids to show. If none are provided, will show all challenges.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Video Overlay</h3>-->
<!--                    <p>-->
<!--                        <code>[video_overlay video_url="https://www.youtube.com/watch?v=frdj1zb9sMY"] //insert image-->
<!--                            here with-->
<!--                            editor [/video_overlay]</code><br>-->
<!--                        Wraps the provided image up as a video play button - when clicked, plays the video in an overlay-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>video_url</td>-->
<!--                            <td>-->
<!--                                The full url from Youtube (go to the video on the youtube website, grab the url)-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>CTA Banner</h3>-->
<!--                    <p>-->
<!--                        <code>[cta_banner button_text="Get it now" button_url="http://www.google.com"]This is the banner-->
<!--                            text[/video_overlay]</code><br>-->
<!--                        Wraps the banner text in a banner and provides a call to action button.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>button_text</td>-->
<!--                            <td>-->
<!--                                The button text-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>button_url</td>-->
<!--                            <td>-->
<!--                                The url the button links to-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>People</h3>-->
<!--                    <p>-->
<!--                        <code>[vival_people group="MANAGEMENT" /]</code> Displays a grid of people that are part of the-->
<!--                        management-->
<!--                        team.-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        <code>[vival_people ids="233, 256, 349" /]</code> Displays a grid of people as defined in the-->
<!--                        list of people-->
<!--                        post ids provided.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>group</td>-->
<!--                            <td>-->
<!--                                Optional. Default: (shows all people if no ids provided). Can be:-->
<!--                                TOP_MANAGEMENT|MANAGEMENT|DIRECTORS.-->
<!--                                Ids don't work with this.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>ids</td>-->
<!--                            <td>-->
<!--                                Optional. You can show a specific list of people by passing in a list of their post ids.-->
<!--                                If none are provided, will show all people (if no team is selected).-->
<!--                                The group argument doesn't work with this.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>orderby</td>-->
<!--                            <td>-->
<!--                                Optional. Default: menu_order. Can be: date|menu_order-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>order</td>-->
<!--                            <td>-->
<!--                                Optional. Default: ASC. Can be: ASC|DESC-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>show_bio</td>-->
<!--                            <td>-->
<!--                                Optional. Default: true. If set to false then clicking the thumbnail won't bring up the-->
<!--                                bio.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Case Studies</h3>-->
<!--                    <p>-->
<!--                        <code>[case_studies ids="233, 256, 349" /]</code> Displays a grid of case studies as defined in-->
<!--                        the list of-->
<!--                        post ids provided.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>ids</td>-->
<!--                            <td>-->
<!--                                Optional. You can show a specific list of case studies by passing in a list of their-->
<!--                                post ids.-->
<!--                                If none are provided, will show all.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>orderby</td>-->
<!--                            <td>-->
<!--                                Optional. Default: menu_order. Can be: date|menu_order-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>order</td>-->
<!--                            <td>-->
<!--                                Optional. Default: ASC. Can be: ASC|DESC-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Case Study</h3>-->
<!--                    <p>-->
<!--                        <code>[case_study id="233" /]</code> Displays a single case study as a page section (do not wrap-->
<!--                        this in anything)-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>id</td>-->
<!--                            <td>-->
<!--                                The case study post id.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Case Studies By Category</h3>-->
<!--                    <p>-->
<!--                        <code>[case_studies_by_cat title="Your header content for this section" /]</code> Displays an-->
<!--                        interactive-->
<!--                        list of case studies, grouped by category-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>title</td>-->
<!--                            <td>-->
<!--                                Optional. The title text at appears at the top of the left-hand nav.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Break on Large</h3>-->
<!--                    <p>-->
<!--                        <code>First Line Here [break_on_large /]Second Line Here</code>-->
<!--                        Inserts a new line which is used only on larger screens (make sure-->
<!--                        there is a space next to it or words around it will be merged).-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        The above would produce:<br>-->
<!--                        <strong>Smaller Screens:</strong><br>-->
<!--                        First Line Here Second Line Here<br>-->
<!--                        <strong>Larger Screens:</strong><br>-->
<!--                        First Line Here<br>-->
<!--                        Second Line Here-->
<!--                    </p>-->
<!--                    <h4>No Arguments</h4>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Locations</h3>-->
<!--                    <p>-->
<!--                        <code>[locations /]</code>-->
<!--                        Inserts thumbnails and links for all locations.-->
<!--                    <h4>No Arguments</h4>-->
<!--                    </p>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Contact Form</h3>-->
<!--                    <p>-->
<!--                        <code>[contact_form industry="Animal Care" topic="SEO" /]</code>-->
<!--                        Inserts contact form.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>industry</td>-->
<!--                            <td>-->
<!--                                If the text matches a value in the dropdown list, this option will be preselected.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>topic</td>-->
<!--                            <td>-->
<!--                                If the text matches a value in the dropdown list, this option will be preselected.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Form Navigation</h3>-->
<!--                    <p>-->
<!--                        <code>[form_navigation ids="125, 147, 802" /]</code>-->
<!--                        Inserts navigation form.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>ids</td>-->
<!--                            <td>-->
<!--                                Optional. Comma separated list of Product Solution IDs that should be shown in the-->
<!--                                "Select Issue"-->
<!--                                dropdown. If empty, all Product Solutions will be shown.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Post Grid</h3>-->
<!--                    <p>-->
<!--                        <code>[post_grid qty=10 category="education" /]</code>-->
<!--                        Inserts grid of latest posts.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>qty</td>-->
<!--                            <td>-->
<!--                                Optional. Number of posts to show. If none are provided, will show 10.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>category</td>-->
<!--                            <td>-->
<!--                                Optional. Which category to pull posts from. It can either be the category "slug" (e.g.-->
<!--                                "seo-stories")-->
<!--                                or the category id (e.g. 15). If a category has "children", it will pull posts from that-->
<!--                                category's-->
<!--                                children as well.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!---->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Button</h3>-->
<!--                    <p>-->
<!--                        <code>[vivial_button text="Button Text" url="http://www.google.com" type="default" size="md"-->
<!--                            target="_blank" /]</code>-->
<!--                        Inserts grid of latest posts.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>text</td>-->
<!--                            <td>-->
<!--                                Button text.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>url</td>-->
<!--                            <td>-->
<!--                                Where the button links to.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>type</td>-->
<!--                            <td>-->
<!--                                Defaults to <code>default</code>. Can be <code>default | primary | danger</code>-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>size</td>-->
<!--                            <td>-->
<!--                                Defaults to <code>md</code>. Can be <code>md | lg | sm | xs</code>-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>target</td>-->
<!--                            <td>-->
<!--                                Goes in the link target attribute. See-->
<!--                                http://www.w3schools.com/jsref/prop_anchor_target.asp-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!---->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Panel</h3>-->
<!--                    <p>-->
<!--                        <code>[vivial_panel]This is the banner text[/vivial_panel]</code>-->
<!--                        Wraps given content in a panel.-->
<!--                    </p>-->
<!--                    <h4>No Arguments</h4>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Footer Contact</h3>-->
<!--                    <p>-->
<!--                        <code>[footer_contact form_title="Contact Us" submit_text="Go Now" dropdowns="true"]this is-->
<!--                            content that-->
<!--                            would go on the right[/footer_contact]</code>-->
<!--                        Inserts a contact form on the right wrapped around editable content on the right.-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>form_title</td>-->
<!--                            <td>-->
<!--                                Title above the form-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>submit_text</td>-->
<!--                            <td>-->
<!--                                The text in the submit button-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>dropdowns</td>-->
<!--                            <td>-->
<!--                                If set to "false", will show text fields instead of dropdowns. Otherwise, it will-->
<!--                                default to dropdowns.-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Dashboard Preview</h3>-->
<!--                    <p>-->
<!--                        <code>[dashboard_preview /]</code>-->
<!--                        Display an interactive dashboard preview.-->
<!--                    </p>-->
<!--                    <h4>No Arguments</h4>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Content Circles</h3>-->
<!--                    <p>-->
<!--                        <code>-->
<!--                            [content_circles]<br>-->
<!--                            [content_circle title="The Title"]<br>-->
<!--                            This is the content that shows if this circle is selected<br>-->
<!--                            [/content_circle]<br>-->
<!--                            [content_circle title="The Title"]<br>-->
<!--                            This is the content that shows if this circle is selected<br>-->
<!--                            [/content_circle]<br>-->
<!--                            [content_circle title="The Title"]<br>-->
<!--                            This is the content that shows if this circle is selected<br>-->
<!--                            [/content_circle]<br>-->
<!--                            [/content_circles]-->
<!--                        </code><br>-->
<!--                        Creates circles that can be clicked to reveal content-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        <code>[content_circles] //all content_circles must be wrapped in here [/content_circles]</code>-->
<!--                        Wrap all content in this-->
<!--                    </p>-->
<!--                    <p>-->
<!--                        <code>[content_circle title="The Title"] //some content [/content_circle]</code> Creates the-->
<!--                        circle title and defines the content that is shown-->
<!--                    </p>-->
<!--                    <h4>Arguments for <code>content_circle</code></h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>title</td>-->
<!--                            <td>-->
<!--                                The title that is shown in the circle-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <h3>Absolute Position</h3>-->
<!--                    <p>-->
<!--                        <code>[absolute_position bottom="15"]Some Content Inside[/absolute_position]</code>-->
<!--                        Wraps content in a absolute-positioned div (only works in relation to the nearest parent that is-->
<!--                        not statically positioned.)-->
<!--                    </p>-->
<!--                    <h4>Arguments</h4>-->
<!--                    <table class="shortcode">-->
<!--                        <tr>-->
<!--                            <td>top</td>-->
<!--                            <td>-->
<!--                                Pixels (without "px") from the top-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>bottom</td>-->
<!--                            <td>-->
<!--                                Pixels (without "px") from the bottom-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>left</td>-->
<!--                            <td>-->
<!--                                Pixels (without "px") from the left-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                        <tr>-->
<!--                            <td>right</td>-->
<!--                            <td>-->
<!--                                Pixels (without "px") from the right-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </table>-->
<!--                </li>-->
<!--            </ul>-->
<!---->
<!--            <hr>-->

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
