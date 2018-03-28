/*
@licstart  The following is the entire license notice for the
JavaScript code in this page.

Copyright (C) 2010  Goteo Foundation

The JavaScript code in this page is free software: you can
redistribute it and/or modify it under the terms of the GNU
General Public License (GNU GPL) as published by the Free Software
Foundation, either version 3 of the License, or (at your option)
any later version.  The code is distributed WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE.  See the GNU GPL for more details.

As additional permission under GNU GPL version 3 section 7, you
may distribute non-source (e.g., minimized or compacted) forms of
that code without the copy of the GNU GPL normally required by
section 4, provided you include this license notice and a URL
through which recipients can access the Corresponding Source.


@licend  The above is the entire license notice
for the JavaScript code in this page.
*/

$(function(){

    var printRaisedGraph = function(target) {
        var hash = location.hash.substr(1).split(',');
        // from hash
        var method = hash[1] || $('#tab-' + target).find('.choose-method a:first').attr('href').substr(1);
        var interval = hash[2] || $('#tab-' + target).find('.choose-interval a:first').attr('href').substr(1);
        if($(this).closest('.invests-filter').length) {
            var part = $(this).attr('href').substr(1);
            var is_method = $(this).closest('.invests-filter').hasClass('choose-method');
            if(is_method) method = part;
            else          interval = part;
        }
        console.log('click', hash, 'part', part, 'target', target, method, interval);
        var id = target  + '-' + method  + '-' + interval;
        location.hash = target  + ',' + method  + ',' + interval;
        $('#tab-' + target).find('.choose-method a[href="#' + method +'"]').closest('li').addClass('active').siblings().removeClass('active');
        $('#tab-' + target).find('.choose-interval a[href="#' + interval +'"]').addClass('active').siblings().removeClass('active');

        var $template =  $('#tab-' + target).find('script#template-' + id + '');
        var $body = $('#tab-' + target).find('.stats-charts');
        // console.log($template.html(), e.target);
        // Create graph
        if($template.length) {
            // console.log('Found template', method, interval, $template.html());
            $body.html($template.html());
            $(window).trigger("autocharts.init");
        } else {
            $body.html('Template [' + id + '] not found!');
        }
    };


    var HASH = location.hash;
    var initBindings = function() {
        HASH = location.hash;
        console.log('initBindings, current hash', HASH);

        $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
          console.log('tab show', e);
          // e.target // newly activated tab
          // e.relatedTarget // previous active tab
          var target = $(e.target).attr('href').substr(1);
          var $menu = $('#tab-' + target).find('.invests-filter');
          $menu.find('a').on('click', function(e) {
            e.preventDefault();
            printRaisedGraph.call(e.target, target);
          });
          printRaisedGraph(target);
        });

        // Click the first
        var hash = HASH.substr(1).split(',') || [];
        if($('#tab-menu-' + hash[0]).length) {
            $('#tab-menu-' + hash[0]).click();
        } else {
            $('a[data-toggle="tab"]:first').click();
        }

        // Change typeahead behaviour
        $('.admin-typeahead').on('typeahead:select', function(event, datum, name) {
            var search = location.search && location.search.substr(1) || '';
            var parts = search.split('&');
            var query = [];
            parts.forEach(function(it){
                if(it.indexOf(name) === 0) return;
                if(it.indexOf('text') === 0) return;
                if(it.indexOf(datum.id) === 0) return;
                query.push(it);
            });
            var href = location.pathname + '?' + (query ? query.join('&') + '&' : '') + name + '=' + datum.id + '&text=' + datum.name;
            console.log('parts',parts,'new query', query, 'href', href);
            adminProntoLoad(href);
        });
    };

    initBindings();
    $(window).on("pronto.render", function(e){
        location.hash = HASH;
        initBindings();
    });


});