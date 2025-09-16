Feature: Manage FinPress rewrites

  Scenario: Change site permastructs
    Given a FIN install

    When I run `fin rewrite structure /blog/%year%/%monthnum%/%day%/%postname%/ --category-base=section --tag-base=topic`
    And I run `fin option get permalink_structure`
    Then STDOUT should contain:
      """
      /blog/%year%/%monthnum%/%day%/%postname%/
      """

    When I run `fin option get category_base`
    Then STDOUT should contain:
      """
      section
      """

    When I run `fin option get tag_base`
    Then STDOUT should contain:
      """
      topic
      """

    When I run `fin rewrite list --format=csv`
    Then STDOUT should be CSV containing:
      | match            | query                               | source   |
      | blog/[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/trackback/?$ | index.php?attachment=$matches[1]&tb=1 | post |
      | topic/([^/]+)/?$ | index.php?tag=$matches[1]           | post_tag |
      | section/(.+?)/?$ | index.php?category_name=$matches[1] | category |

    When I run `fin rewrite list --match=/topic/apple/ --format=csv --fields=match,query`
    Then STDOUT should be CSV containing:
      | match            | query                               |
      | topic/([^/]+)/?$ | index.php?tag=$matches[1]           |
    And STDOUT should not contain:
      """
      source
      """

  Scenario: Missing permalink_structure
    Given a FIN install

    When I run `fin option delete permalink_structure`
    And I try `fin option get permalink_structure`
    Then STDOUT should be empty

    When I try `fin rewrite flush`
    Then STDERR should contain:
      """
      Warning: Rewrite rules are empty, possibly because of a missing permalink_structure option.
      """
    And STDOUT should be empty

    When I run `fin rewrite structure /%year%/%monthnum%/%day%/%postname%/`
    And I run `fin rewrite flush`
    Then STDOUT should be:
      """
      Success: Rewrite rules flushed.
      """

  Scenario: Generate .htaccess on hard flush with a project config
    Given a FIN install
    And a fin-cli.yml file:
      """
      apache_modules: [mod_rewrite]
      """

    When I run `fin rewrite structure /%year%/%monthnum%/%day%/%postname%/ --hard`
    Then the .htaccess file should exist
    And the return code should be 0
    And STDERR should be empty

  Scenario: Error when trying to generate .htaccess on a multisite install
    Given a FIN multisite install
    And a fin-cli.yml file:
      """
      apache_modules: [mod_rewrite]
      """

    When I try `fin rewrite flush --hard`
    Then STDERR should be:
      """
      Warning: FinPress can't generate .htaccess file for a multisite install.
      """
    And STDOUT should be:
      """
      Success: Rewrite rules flushed.
      """
    And the return code should be 0

    When I try `fin rewrite structure /%year%/%monthnum%/%day%/%postname%/ --hard`
    Then STDERR should contain:
      """
      Warning: FinPress can't generate .htaccess file for a multisite install.
      """
    And STDOUT should be:
      """
      Success: Rewrite structure set.
      Success: Rewrite rules flushed.
      """
    And the return code should be 0

  Scenario: Warn the user when --skip-plugins or --skip-themes is used
    Given a FIN install

    When I try `fin --skip-plugins rewrite flush`
    Then STDERR should contain:
      """
      Warning: Some rewrite rules may be missing because plugins weren't loaded by FIN-CLI.
      """
    And the return code should be 0

    When I try `fin --skip-plugins --skip-themes rewrite flush`
    Then STDERR should contain:
      """
      Warning: Some rewrite rules may be missing because plugins and themes weren't loaded by FIN-CLI.
      """
    And the return code should be 0

    When I try `fin rewrite flush`
    Then STDERR should not contain:
      """
      Warning: Some rewrite rules may be missing
      """
    And the return code should be 0

  Scenario: Match as expected when full URL is provided
    Given a FIN install
    And I run `fin rewrite structure /%year%/%monthnum%/%day%/%postname%/`

    When I run `fin rewrite list --match=/2022/11/13/hello-world/ --format=csv --fields=query,source`
    Then STDOUT should be CSV containing:
      | query                               | source   |
      | index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&page=$matches[5] | post |
      | index.php?pagename=$matches[1]&page=$matches[2]           | page |

    When I run `fin rewrite list --match=https://example.com/2022/11/13/hello-world/ --format=csv --fields=query,source`
    Then STDOUT should be CSV containing:
      | query                               | source   |
      | index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&page=$matches[5] | post |
      | index.php?pagename=$matches[1]&page=$matches[2]           | page |
