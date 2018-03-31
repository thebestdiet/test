<?php if (!defined('APPLICATION')) exit();

if (!function_exists('CategoryHeading')):

    /**
     * Write the category heading in a category table.
     * Good for plugins that want to override whats displayed in the heading to the category name.
     *
     * @return string
     * @since 2.1
     */
    function categoryHeading() {
        return t('Categories');
    }

endif;

if (!function_exists('CategoryPhoto')):

    /**
     *
     * @since 2.1
     */
    function categoryPhoto($row) {
        $photoUrl = val('PhotoUrl', $row);

        if ($photoUrl) {
            $result = anchor(
                '<img src="'.$photoUrl.'" class="CategoryPhoto" alt="'.htmlspecialchars(val('Name', $row)).'" />',
                categoryUrl($row, '', '//'),
                'Item-Icon PhotoWrap PhotoWrap-Category');
        } else {
            $result = anchor(
                '<span class="sr-only">'.t('Expand for more options.').'</span>',
                categoryUrl($row, '', '//'),
                'Item-Icon PhotoWrap PhotoWrap-Category Hidden NoPhoto');
        }

        return $result;
    }

endif;

if (!function_exists('CategoryString')):

    function categoryString($rows) {
        $result = '';
        foreach ($rows as $row) {
            if ($result)
                $result .= '<span class="Comma">, </span>';
            $result .= anchor(htmlspecialchars($row['Name']), $row['Url']);
        }
        return $result;
    }
endif;

if (!function_exists('getOptions')):
    /**
     * Render options that the user has for this category. Returns an empty string if the session isn't valid.
     *
     * @param $category The category to render the options for.
     * @return DropdownModule|string A dropdown with the category options or an empty string if the session is not valid.
     * @throws Exception
     */
    function getOptions($category) {
        if (!Gdn::session()->isValid()) {
            return '';
        }
        $sender = Gdn::controller();
        $categoryID = val('CategoryID', $category);

        $dropdown = new DropdownModule('dropdown', '', 'OptionsMenu');
        $tk = urlencode(Gdn::session()->transientKey());
        $hide = (int)!val('Following', $category);

        $dropdown->addLink(t('Mark Read'), "/category/markread?categoryid={$categoryID}&tkey={$tk}", 'mark-read');
        $dropdown->addLink(t($hide ? 'Unmute' : 'Mute'), "/category/follow?categoryid={$categoryID}&value={$hide}&tkey={$tk}", 'hide');

        // Allow plugins to add options
        $sender->EventArguments['CategoryOptionsDropdown'] = &$dropdown;
        $sender->EventArguments['Category'] = &$category;
        $sender->fireEvent('CategoryOptionsDropdown');

        return $dropdown;
    }
endif;

if (!function_exists('MostRecentString')):
    function mostRecentString($row) {
        if (!$row['LastTitle'])
            return '';

        $r = '';

        $r .= '<span class="MostRecent">';
        $r .= '<span class="MLabel">'.t('Most recent:').'</span> ';
        $r .= anchor(
            sliceString(Gdn_Format::text($row['LastTitle']), 150),
            $row['LastUrl'],
            'LatestPostTitle');

        if (val('LastName', $row)) {
            $r .= ' ';

            $r .= '<span class="MostRecentBy">'.t('by').' ';
            $r .= userAnchor($row, 'UserLink', 'Last');
            $r .= '</span>';
        }

        if (val('LastDateInserted', $row)) {
            $r .= ' ';

            $r .= '<span class="MostRecentOn">';
            $r .= t('on').' ';
            $r .= anchor(
                Gdn_Format::date($row['LastDateInserted'], 'html'),
                $row['LastUrl'],
                'CommentDate');
            $r .= '</span>';
        }

        $r .= '</span>';

        return $r;
    }
endif;

if (!function_exists('writeListItem')):
    /**
     * Renders a list item in a category list (modern view).
     *
     * @param $category
     * @param $depth
     * @throws Exception
     */
    function writeListItem($category, $depth) {
        $children = $category['Children'];
        $categoryID = val('CategoryID', $category);
        $cssClass = cssClass($category);
        $writeChildren = getWriteChildrenMethod($category, $depth);
        $rssIcon = '';

        if (val('DisplayAs', $category) === 'Discussions') {
            $rssImage = img('applications/dashboard/design/images/rss.gif', ['alt' => t('RSS Feed')]);
            $rssIcon = anchor($rssImage, '/categories/'.val('UrlCode', $category).'/feed.rss', '', ['title' => t('RSS Feed')]);
        }

        if (val('DisplayAs', $category) === 'Heading') : ?>
            <li id="Category_<?php echo $categoryID; ?>" class="CategoryHeading <?php echo $cssClass; ?>">
                <div class="ItemContent Category">
                    <div class="Options"><?php echo getOptions($category); ?></div>
                    <?php echo Gdn_Format::text(val('Name', $category)); ?>
                </div>
            </li>
        <?php else: ?>
            <li id="Category_<?php echo $categoryID; ?>" class="<?php echo $cssClass; ?>">
                <?php
                Gdn::controller()->EventArguments['ChildCategories'] = &$children;
                Gdn::controller()->EventArguments['Category'] = &$category;
                Gdn::controller()->fireEvent('BeforeCategoryItem');
                ?>
                <div class="ItemContent Category">
                    <div class="Options">
                        <?php echo getOptions($category) ?>
                    </div>
                    <?php echo categoryPhoto($category); ?>
                    <div class="TitleWrap">
                        <?php echo anchor(Gdn_Format::text(val('Name', $category)), categoryUrl($category), 'Title');
                        Gdn::controller()->fireEvent('AfterCategoryTitle');
                        ?>
                    </div>
                    <div class="CategoryDescription">
                        <?php echo val('Description', $category) ?>
                    </div>
                    <div class="Meta">
                        <span class="MItem RSS"><?php echo $rssIcon ?></span>
                        <span class="MItem DiscussionCount">
                            <?php echo sprintf(
                                pluralTranslate(
                                    val('CountAllDiscussions', $category),
                                    '%s discussion html',
                                    '%s discussions html',
                                    t('%s discussion'),
                                    t('%s discussions')
                                ), bigPlural(val('CountAllDiscussions', $category), '%s discussion')) ?>
                        </span>
                        <span class="MItem CommentCount">
                            <?php echo sprintf(
                                pluralTranslate(
                                    val('CountAllComments', $category), '%s comment html',
                                    '%s comments html',
                                    t('%s comment'),
                                    t('%s comments')
                                ), bigPlural(val('CountAllComments', $category), '%s comment')); ?>
                        </span>

                        <?php if (val('LastTitle', $category) != '') : ?>
                            <span class="MItem LastDiscussionTitle">
                                <?php echo mostRecentString($category); ?>
                            </span>
                            <span class="MItem LastCommentDate">
                                <?php echo Gdn_Format::date(val('LastDateInserted', $category)); ?>
                            </span>
                        <?php endif;
                        if ($writeChildren === 'list'): ?>
                            <div class="ChildCategories">
                                <?php echo wrap(t('Child Categories').': ', 'b'); ?>
                                <?php echo categoryString($children, $depth + 1); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endif;
        if ($writeChildren === 'items') {
            foreach ($children as $child) {
                writeListItem($child, $depth + 1);
            }
        }
    }
endif;


if (!function_exists('WriteTableHead')):

    function writeTableHead() {
        ?>
        <tr>
            <td class="CategoryName">
                <div class="Wrap"><?php echo categoryHeading(); ?></div>
            </td>
            <td class="BigCount CountDiscussions">
                <div class="Wrap"><?php echo t('Discussions'); ?></div>
            </td>
            <td class="BigCount CountComments">
                <div class="Wrap"><?php echo t('Comments'); ?></div>
            </td>
            <td class="BlockColumn LatestPost">
                <div class="Wrap"><?php echo t('Latest Post'); ?></div>
            </td>
        </tr>
    <?php
    }
endif;

if (!function_exists('WriteTableRow')):

    function writeTableRow($row, $depth = 1) {
        $children = $row['Children'];
        $writeChildren = getWriteChildrenMethod($row, $depth);
        $h = 'h'.($depth + 1);
        ?>
        <tr class="<?php echo cssClass($row); ?>">
            <td class="CategoryName">
                <div class="Wrap">
                    <?php
                    echo '<div class="Options">'.getOptions($row).'</div>';

                    echo categoryPhoto($row);

                    echo "<{$h}>";
                    $safeName = htmlspecialchars($row['Name']);
                    echo $row['DisplayAs'] === 'Heading' ? $safeName : anchor($safeName, $row['Url']);
                    Gdn::controller()->EventArguments['Category'] = $row;
                    Gdn::controller()->fireEvent('AfterCategoryTitle');
                    echo "</{$h}>";
                    ?>
                    <div class="CategoryDescription">
                        <?php echo $row['Description']; ?>
                    </div>
                    <?php if ($writeChildren === 'list'): ?>
                        <div class="ChildCategories">
                            <?php
                            echo wrap(t('Child Categories').': ', 'b');
                            echo categoryString($children, $depth + 1);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="BigCount CountDiscussions">
                <div class="Wrap">
                    <?php
                    //            echo "({$Row['CountDiscussions']})";
                    echo bigPlural($row['CountAllDiscussions'], '%s discussion');
                    ?>
                </div>
            </td>
            <td class="BigCount CountComments">
                <div class="Wrap">
                    <?php
                    //            echo "({$Row['CountComments']})";
                    echo bigPlural($row['CountAllComments'], '%s comment');
                    ?>
                </div>
            </td>
            <td class="BlockColumn LatestPost">
                <div class="Block Wrap">
                    <?php if ($row['LastTitle']): ?>
                        <?php
                        echo userPhoto($row, ['Size' => 'Small', 'Px' => 'Last']);
                        echo anchor(
                            sliceString(Gdn_Format::text($row['LastTitle']), 100),
                            $row['LastUrl'],
                            'BlockTitle LatestPostTitle',
                            ['title' => html_entity_decode($row['LastTitle'])]);
                        ?>
                        <div class="Meta">
                            <?php
                            echo userAnchor($row, 'UserLink MItem', 'Last');
                            ?>
                            <span class="Bullet">•</span>
                            <?php
                            echo anchor(
                                Gdn_Format::date($row['LastDateInserted'], 'html'),
                                $row['LastUrl'],
                                'CommentDate MItem');

                            if (isset($row['LastCategoryID'])) {
                                $lastCategory = CategoryModel::categories($row['LastCategoryID']);

                                echo ' <span>',
                                sprintf('in %s', anchor($lastCategory['Name'], categoryUrl($lastCategory, '', '//'))),
                                '</span>';

                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
        if ($writeChildren === 'items') {
            foreach ($children as $childRow) {
                writeTableRow($childRow, $depth + 1);
            }
        }
    }
endif;

if (!function_exists('writeCategoryList')):
    /**
     * Renders a category list (modern view).
     *
     * @param $categories
     * @param int $depth
     */
    function writeCategoryList($categories, $depth = 1) {
        ?>
        <div class="DataListWrap">
            <ul class="DataList CategoryList">
                <?php
                foreach ($categories as $category) {
                    writeListItem($category, $depth);
                }
                ?>
            </ul>
        </div>
        <?php
    }
endif;

if (!function_exists('writeCategoryTable')):
    function writeCategoryTable($categories, $depth = 1, $inTable = false) {
        foreach ($categories as $category) {
            $displayAs = val('DisplayAs', $category);
            $urlCode = $category['UrlCode'];
            $class = val('CssClass', $category);
            $name = htmlspecialchars($category['Name']);

            if ($displayAs === 'Heading') :
                if ($inTable) {
                    echo '</tbody></table></div>';
                    $inTable = false;
                }
                ?>
                <div id="CategoryGroup-<?php echo $urlCode; ?>" class="CategoryGroup <?php echo $class; ?>">
                    <h2 class="H"><?php echo $name; ?></h2>
                    <?php writeCategoryTable($category['Children'], $depth + 1, $inTable); ?>
                </div>
                <?php
            else :
                if (!$inTable) { ?>
                    <div class="DataTableWrap">
                        <table class="DataTable CategoryTable">
                            <thead>
                            <?php writeTableHead(); ?>
                            </thead>
                            <tbody>
                    <?php $inTable = true;
                }
                writeTableRow($category, $depth);
            endif;
        }
        if ($inTable) {
            echo '</tbody></table></div>';
        }
    }
endif;

if (!function_exists('getWriteChildrenMethod')):
    /**
     * Calculates how to display category children. Either 'list' for a comma-separated list (usually appears in meta) or
     * 'items' to nest children below the parent or false if there are no children.
     *
     * @param $category
     * @param $depth
     * @return bool|string
     */
    function getWriteChildrenMethod($category, $depth) {
        $children = val('Children', $category);
        $writeChildren = false;
        $maxDisplayDepth = c('Vanilla.Categories.MaxDisplayDepth');
        $isHeading = val('DisplayAs', $category) === 'Heading';

        if (!empty($children)) {
            if (!$isHeading && $maxDisplayDepth > 0 && ($depth + 1) >= $maxDisplayDepth) {
                $writeChildren = 'list';
            } else {
                $writeChildren = 'items';
            }
        }

        return $writeChildren;
    }
endif;