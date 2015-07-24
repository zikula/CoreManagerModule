<?php
/**
 * Created by PhpStorm.
 * User: Christian
 * Date: 23.07.2015
 * Time: 11:07
 */

namespace Zikula\Module\CoreManagerModule;

class Settings
{
    const RELEASE_CANDIDATE_PROMOTION_ID = 1;
    const RELEASE_PROMOTION_ID = 2;
    public static $QA_ISSUE_LABELS = ['Blocker', 'meta', 'Discussion', 'CI Build'];

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

Announcement
------------
%DESCRIPTION%
EOD;

}
