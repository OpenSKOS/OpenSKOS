/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

var EditorLabel = new Class({
    Binds: [],
    initialize: function () {
        $(document.body).addEvent('click:relay(.create-label-link)', function (ev) {
            ev.stop();
            SqueezeBox.open(ev.target.getProperty('href'), {size: {x: 450, y: 500}, handler: 'iframe'});
        });
    }
});