/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

var config = {
    map: {
        '*': {
            'robotsTagSelector': 'Hryvinskyi_SeoRobotsAdminUi/js/robots-tag-selector'
        }
    },
    shim: {
        'Hryvinskyi_SeoRobotsAdminUi/js/robots-tag-selector': {
            deps: ['jquery']
        }
    }
};
