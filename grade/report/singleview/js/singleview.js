M.gradereport_singleview = {};

M.gradereport_singleview.init = function(Y) {
    // Make toggle links
    Y.all('.include').each(function(link) {
        var type = link.getAttribute('class').split(" ")[2];

        var toggle = function(checked) {
            return function(input) {
                input.getDOMNode().checked = checked;
                Y.Event.simulate(input.getDOMNode(), 'change');
            };
        };

        link.on('click', function(e) {
            e.preventDefault();
            Y.all('input[name^=' + type + ']').each(toggle(link.hasClass('all')));
        });
    });

    //MOOD-762 20150306 jinhofer Add Click function to the button
    if(Y.one('button#toggle-search')) {
        Y.one('button#toggle-search').on('click', function() {
            Y.all('div.singleselect select').each(function() {
                if(this.get('size') == '15') {
                    this.setAttribute('size', '1');
                    Y.all('div.filter').each(function() {
                        this.addClass('hidden');
                    });
                }
                else {
                    this.setAttribute('size', '15');
                    Y.all('div.filter').each(function() {
                        this.removeClass('hidden');
                    });
                }
            })
        });
    }

    //MOOD-762 20150306 jinhofer Add the filter input boxes for each select
    Y.all('div.singleselect').each(function() {
        if(this.one('input[name=item]').getAttribute('value') == 'grade') {
            id = 'grade-search';
        }
        else {
            id = 'user-search';
        }
        this.append('<div class="hidden filter">'+M.util.get_string('searchlist', 'gradereport_singleview')+'<br><input id="'+id+'" type="text" autocomplete="off"></div>');
    });

    //MOOD-762 20150306 jinhofer Handle search srtings put into the input boxes
    Y.all('div.filter input').each(function() {
        this.on('valueChange', function(e) {
            if(this.get('value') = '') {
                this.ancestor('div.singleselect').all('select option').each(function() {
                    this.removeClass('hidden');
                });
                return;
            }
            this.ancestor('div.singleselect').all('select option').each(function() {
                value = this.getHTML().toLowerCase();
                text = this.ancestor('div.singleselect').one('div.filter input').get('value').toLowerCase();
                if(value.indexOf(text) == -1 || this.get('value') == '' && text != '') {
                    this.addClass('hidden');
                }
                else {
                    this.removeClass('hidden');
                }
            });
        });
    });

    // Override Toggle
    Y.all('input[name^=override_]').each(function(input) {
        input.on('change', function() {
            var checked = input.getDOMNode().checked;
            var names = input.getAttribute('name').split("_");

            var itemid = names[1];
            var userid = names[2];

            var interest = '_' + itemid + '_' + userid;

            Y.all('input[name$=' + interest + ']').filter('input[type=text]').each(function(text) {
                text.getDOMNode().disabled = !checked;
            });
            // deal with scales that are not text... UCSB
            Y.all('select[name$=' + interest + ']').each(function(select) {
                select.getDOMNode().disabled = !checked;
            });
        });
    });
};
