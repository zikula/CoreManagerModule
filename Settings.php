<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule;

class Settings
{
    /**
     * The default release title.
     */
    const RELEASE_TITLE = 'Zikula Core %s';

    /**
     * The default release announcement.
     */
    const RELEASE_ANNOUNCEMENT = 'Zikula Core **%1$s** is available as of today, %2$s.';

    /**
     * The text to add to the default title if it is a pre release.
     */
    const RELEASE_CANDIDATE_TITLE_AMENDMENT = ' Release Candidate %s';

    /**
     * The text to add to the default release announcement if it is a pre release.
     */
    const RELEASE_CANDIDATE_ANNOUNCEMENT_AMENDMENT = <<<EOD


Immediate testing is encouraged. Release testing guidelines may be found in [the docs](https://docs.ziku.la/General/Releases/releasetestingguidelines.html). 
At the same site [installation](https://docs.ziku.la/Setup/installation.html) and [upgrade](https://docs.ziku.la/Setup/upgrade.html) documentation can be found, too.

Our Quality Assurance cycle, explained also in [the release management docs](https://docs.ziku.la/General/Releases/releasemanagement.html#release-candidates-rc), 
will be followed in order to achieve our General Release. Please register your vote on the promotion of this build in the [promotion ticket](%QATICKETURL%).

Please report all bugs and concerns to our [issue tracker on Github](https://github.com/zikula/core/issues). Please 
understand that bugs will not necessarily halt the release of this build. Bugs may be fixed or postponed to another release.
# de:Zikula Core %1\$s
Der Zikula Core ist in der Version %2\$s ab heute, %3\$s, verfügbar.


Sofortige Tests werden empfohlen. Richtlinien für das Testen von Releases finden sich in [der Dokumentation] (https://docs.ziku.la/General/Releases/releasetestingguidelines.html). 
Auf derselben Seite werden auch [Installation](https://docs.ziku.la/Setup/installation.html) und [Upgrade](https://docs.ziku.la/Setup/upgrade.html) erklärt.

Unser Qualitätssicherungszyklus, der auch in [den Docs zum Release-Management](https://docs.ziku.la/General/Releases/releasemanagement.html#release-candidates-rc) erläutert wird, 
wird befolgt, um das finale Release zu erreichen. Feedback zur Freigabe dieses Builds kann im [Promotion-Ticket](%QATICKETURL%) abgegeben werden.

Bitte alle Fehler und Bedenken im [Issue Tracker auf Github](https://github.com/zikula/core/issues) melden. Wir bitten um Verständnis dafür,
dass Fehler die Veröffentlichung dieses Builds nicht unbedingt aufhalten werden. Fehler können ggf. eine andere Version verschoben und dort behoben werden.

EOD;

    /**
     * The core file containing the static version number.
     */
    const CORE_PHP_FILE = 'src/Zikula/CoreBundle/HttpKernel/ZikulaKernel.php';

    /**
     * Regexp to match the version string in the above file.
     */
    const CORE_PHP_FILE_VERSION_REGEXP = '[^_]VERSION\s*=\s*(?:\'|")(.*?)(?:\'|")';

    /**
     * @var array Labels to add to the quality assurance issue.
     */
    public static $QA_ISSUE_LABELS = ['Blocker', 'Feedback required'];

    /**
     * @var string Template to use for the quality assurance issue.
     */
    public static $QA_ISSUE_TEMPLATE = <<<EOD
Please test this pre-release and decide if it should become the next official release.
**Anyone may participate in the testing process.**

Testing guidelines can be found in [Release Testing Guideline](https://docs.ziku.la/General/Releases/releasetestingguidelines.html)

Major and Minor Feature Releases require three +1 votes to promote the build and a minimum testing period
of three days testing before the build can pass. Two "-1" votes (with reason) will cause us to fail the build.
If this build fails, **votes cannot be transferred** to the new release candidate, **testing must resume
from the beginning**. If the negative threshold is not reached before the posted deadline, then the build
passes automatically.

Please **do not** report bugs in this ticket, only register your approval or disapproval. You must give
a reason and reference if appropriate (e.g. link to a ticket) for negative votes.

**Please report issues in a separate ticket.**

### Notes, References, and/or Special Instructions

Do not vote negatively if you find non-release blocking bugs. Minor and major bugs may be scheduled in a future version.
EOD;
}
