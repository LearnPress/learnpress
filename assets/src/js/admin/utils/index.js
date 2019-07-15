// global utilities
import * as Utils from '../../utils';

// local utilities
import dismissNotice from './admin-notice';
import Debug  from './debug-log';
import Advertisement from './advertisement';
import DropdownPages from './dropdown-pages';
import AdvancedList from './advanced-list';
import AdminTabs from './admin-tabs';
import isEmail from '../../utils/email-validator';

import CL from './conditional-logic';

export default {
    ...Utils,
    dismissNotice,
    Debug,
    Advertisement,
    DropdownPages,
    AdvancedList,
    AdminTabs,
    isEmail
}