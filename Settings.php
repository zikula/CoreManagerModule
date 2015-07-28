<?php

namespace Zikula\Module\CoreManagerModule;

class Settings
{
    /**
     * The promotion level used at Jenkins to promote a release candidate.
     */
    const RELEASE_CANDIDATE_PROMOTION_ID = 1;

    /**
     * The promotion level used at Jenkins to promote a release.
     */
    const RELEASE_PROMOTION_ID = 2;

    /**
     * The default release title.
     */
    const RELEASE_TITLE = 'Zikula Core %s';

    /**
     * The default release announcement.
     */
    const RELEASE_ANNOUNCEMENT = 'Zikula Core **%s** is available as of today, %s.';

    /**
     * The text to add to the default title if it is a pre release.
     */
    const RELEASE_CANDIDATE_TITLE_AMENDMENT = " Release Candidate %s";

    /**
     * The text to add to the default release announcement if it is a pre release.
     */
    const RELEASE_CANDIDATE_ANNOUNCEMENT_AMENDMENT = <<<EOD


Immediate testing is encouraged. You may download the RC from our [links at zikula.org](http://zikula.org/library/releases). Release testing guidelines may be found in [the Core wiki](https://github.com/zikula/core/wiki/Release-Testing-Guidelines). Installation and upgrade documentation can be found in the /docs directory.

Our Quality Assurance cycle, explained also in [the Core wiki](https://github.com/zikula/core/wiki/Release-Management#release-candidates-rc), will be followed in order to achieve our General Release. Please register your vote on the promotion of this build in the [promotion ticket](%QATICKETURL%).

Please report all bugs and concerns to our [issue tracker on Github](https://github.com/zikula/core/issues). Please understand that bugs will not necessarily halt the release of this build. Bugs may be fixed or postponed to another release.
EOD;
    const CORE_PHP_FILE = 'src/lib/legacy/Zikula/Core.php';
    const CORE_PHP_FILE_VERSION_REGEXP = 'VERSION_NUM\s*=\s*(?:\'|")(.*?)(?:\'|")';

    /**
     * @var array Labels to add to the quality assurance issue.
     */
    public static $QA_ISSUE_LABELS = ['Blocker', 'meta', 'Discussion', 'CI Build'];

    /**
     * @var string Template to use for the quality assurance issue.
     */
    public static $QA_ISSUE_TEMPLATE = <<<EOD
Please test build [#%BUILD%](http://ci.zikula.org/job/%JOB%/%BUILD%/) and decide if it should become the next official release.
__Anyone may participate in the testing process.__

Testing guidelines can be found in [Release Testing Guideline](https://github.com/zikula/core/wiki/Release-Testing-Guidelines)

We require five +1 votes to promote this build and a minimum testing period of three days testing before the build can pass.
Two "-1" votes (with reason) will cause us to fail the build. If this build fails, __votes cannot be transferred__ to
the new release candidate, __testing must resume from the beginning__.

Please **do not** report bugs in this ticket, only register your approval or disapproval. You must give a reason and
reference if appropriate (e.g. link to a ticket) for negative votes.

**Please report issues in a separate ticket.**

Notes, References, and/or Special Instructions
---------------------------------------------------------

Do not vote negatively if you find non-release blocking bugs.  Minor and major bugs may be scheduled in a future version.
EOD;
}
