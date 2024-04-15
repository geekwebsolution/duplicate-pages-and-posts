var el = React.createElement;
var Fragment = wp.element.Fragment;
var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
var registerPlugin = wp.plugins.registerPlugin;     // import registerplugin method

function Component() {
    return el(
        Fragment,
        {},
        el(
            PluginPostStatusInfo,
            'div',
            React.createElement('a', { href:gwdpObj.duplicatepostlink, class:'button button-primary button-large'}, gwdpObj.duplicatepostlinktext)
        )
    );
}
registerPlugin( 'gwdp-duplicate-post', {
    render: Component
} );