global._ = require('lodash');

Nova.booting((Vue, router) => {
    Vue.component('index-permission', require('./components/PermissionIndexField'))
    Vue.component('detail-permission', require('./components/PermissionDetailField'))
    Vue.component('form-permission', require('./components/PermissionFormField'))
    Vue.component("multiselect", require("vue-multiselect").default);
    Vue.component('permissions-tool', require('./components/PermissionsTool').default);
    Vue.component("permissions", require('./components/Permissions').default);
    Vue.component('team-switcher', require('./components/TeamSwitcher').default);
    router.addRoutes([
        {
            name: 'laravel-nova-governor-roles',
            path: '/laravel-nova-governor/roles',
            component: require('./components/Roles').default,
        },
        {
            name: 'laravel-nova-governor-role-create',
            path: '/laravel-nova-governor/roles/create',
            component: require('./components/RoleCreate').default,
        },
        {
            name: 'laravel-nova-governor-permissions',
            path: '/laravel-nova-governor/permissions/:role',
            component: require('./components/Permissions').default,
        },
        {
            name: 'laravel-nova-governor-groups',
            path: '/laravel-nova-governor/groups',
            component: require('./components/Groups').default,
        },
        {
            name: 'laravel-nova-governor-groups-create',
            path: '/laravel-nova-governor/groups/create/',
            component: require('./components/GroupCreate').default,
        },
        {
            name: 'laravel-nova-governor-groups-edit',
            path: '/laravel-nova-governor/groups/:group',
            component: require('./components/GroupCreate').default,
        },
        {
            name: 'laravel-nova-governor-assignments',
            path: '/laravel-nova-governor/assignments',
            component: require('./components/Assignments').default,
        },
    ])
})
