
YUI.add('moodle-local_configutil-compareconfig', function(Y) {

var compareConfig = function() {
    compareConfig.superclass.constructor.apply(this, arguments);
}

Y.extend(compareConfig, Y.Base, {

    sectionfilter : null,
    nonmatchonly: false,

    initializer : function(params) {

        var instance = this;

        // Set up toggle for displaying non-match only.
        var controls_div = Y.one('#configutil_compareconfig_controls');
        if (controls_div) {
            var nonmatchonly_btn = Y.Node.create('<button/>');
            nonmatchonly_btn.setContent('Show non-matching settings only');

            controls_div.append(nonmatchonly_btn);

            nonmatchonly_btn.on('click', function (e) {
                if (instance.nonmatchonly) {
                    if (instance.sectionfilter) {
                        Y.all('tr.settingmatch.'+instance.sectionfilter).setStyle('display', 'table-row');
                    } else {
                        Y.all('tr.settingmatch').setStyle('display', 'table-row');
                    }
                    nonmatchonly_btn.setContent('Show non-matching settings only');
                    instance.nonmatchonly = false;
                } else {
                    Y.all('tr.settingmatch').setStyle('display', 'none');
                    nonmatchonly_btn.setContent('Show matching settings, also');
                    instance.nonmatchonly = true;
                }
            });
        }

        // Set up toggle for filtering on configuration section.
        var configtable = Y.one('.configtablewrapper table');
        if (configtable) {
            configtable.delegate('click',
                                 instance.filtersection,
                                 '.sectionname .filtertarget',
                                 instance);
        }
    },

    filtersection : function (e) {

        if (this.sectionfilter) {
            if (this.nonmatchonly) {
                Y.all('.configtablewrapper table tr.nosettingmatch').setStyle('display', 'table-row');
            } else {
                Y.all('.configtablewrapper table tr').setStyle('display', 'table-row');
            }
            this.sectionfilter = null;
        } else {
            sectionnames = this.get('sectionnames');
            sectionnames.push('unknown');

            for (var i=0; i<sectionnames.length; i++) {
                if (! e.target.ancestor('tr').hasClass(sectionnames[i])) {
                    Y.all('.configtablewrapper table tr.'+sectionnames[i]).setStyle('display', 'none');
                } else {
                    this.sectionfilter = sectionnames[i];
                }
            }
            window.scroll(0,0);
        }
    }
},
{
    NAME  : 'configutil_compareconfig',
    ATTRS : {
                sectionnames : {}
            }
});

M.local_configutil = M.local_configutil || {};
M.local_configutil.init_compareConfig = function(params) {
    return new compareConfig(params);
}

},
'@VERSION@',
 { requires:['base']});
