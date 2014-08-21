@block @block_dataformnotification @mod_dataform @dataformrule
Feature: Block dataform notification

    @javascript
    Scenario: Manage notification rule
        Given I run dataform scenario "manage notification rule" with:
            | ruletype |  |