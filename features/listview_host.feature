@listview
Feature: Listview Host

	Background:
		Given I am logged in as administrator

	Scenario Outline: Actively/Passively checked icons are shown
		appropriately on host list view

		Given I have these mocked hosts
			| name       | state   | checks_enabled   | accept_passive_checks |
			| Keckifari  | 0       | <active_checks>  | <passive_checks>      |

		Given I am on a hosts list view
		Then I should see an icon with title "<text1>"
		And I should see an icon with title "<text2>"

		Examples:
			| active_checks | passive_checks | text1                  | text2                   |
			| 0             | 0              | Active checks disabled | Passive checks disabled |
			| 0             | 1              | Active checks disabled | Passive checks enabled  |
			| 1             | 1              | Active checks enabled  | Passive checks enabled  |
			| 1             | 0              | Active checks enabled  | Passive checks disabled |
