/**
 * Create app
 *
 * @since 2.0.0
 */
var ingotApp = angular.module('ingotApp', [
    'ui.router',
    'ui.bootstrap',
    'colorpicker.module',
    'ngAria',
    'ngResource'
] )
    .run( function( $rootScope, $state ) {
        $rootScope.translate =  INGOT_TRANSLATION;
		
		$rootScope.$on('$stateChangeSuccess', function(event, toState, toParams, fromState, fromParams){
			
			if( toState.name == 'clickTests' ) {
				$rootScope.main_click_tests_page = true;
			} else {
				$rootScope.main_click_tests_page = false;
			}
			
		});
		$rootScope.isActiveNav = function( page ) {
			
			if( !$state.current.name ) { return }			
			if( $state.current.name.indexOf( page ) >= 0 ) {
				return 'active';
			}
			
		}
		
    }
);

/**
 * Router
 *
 * @since 2.0.0
 */
ingotApp.config(function($stateProvider, $urlRouterProvider) {

    $urlRouterProvider.otherwise("/");
    $stateProvider
        //click tests
        .state('clickTests', {
            url: "/click-tests",
            templateUrl: INGOT_ADMIN.partials + "/click-groups.html"
        })
        .state('clickTests.list', {
            url: "/click-tests/all",
            templateUrl: INGOT_ADMIN.partials + "/click-groups.list.html",
            controller: 'clickGroups'
        } )
        .state('clickTests.edit', {
            url: "/click-tests/edit/:groupID",
            templateUrl: INGOT_ADMIN.partials + "/click-group.html",
            controller: 'clickGroup'
        } )
        .state('clickTests.new', {
            url: "/click-tests/new",
            templateUrl: INGOT_ADMIN.partials + "/click-group.html",
            controller: 'clickGroup'
        } ).state('clickTests.delete', {
            url: "/click-tests/delete/:groupID",
            controller: 'clickDelete'
        }).state('clickTests.stats', {
            url: "/click-tests/stats/:groupID",
            templateUrl: INGOT_ADMIN.partials + "/click-group.stats.html",
            controller: 'clickStats'
        } )
        //price tests
        .state('priceTests', {
            url: "/price-tests",
            templateUrl: INGOT_ADMIN.partials + "/price-groups.html",
            controller: 'priceGroups'
        })
        .state('priceTests.list', {
            url: "/price-tests/all",
            templateUrl: INGOT_ADMIN.partials + "/price-groups.list.html",
            controller: 'priceGroups'
        } )
        .state('priceTests.edit', {
            url: "/price-tests/edit/:groupID",
            templateUrl: INGOT_ADMIN.partials + "/price-group.html",
            controller: 'priceGroup'
        } )
        .state('priceTests.new', {
            url: "/price-tests/new/",
            templateUrl: INGOT_ADMIN.partials + "/price-group.html",
            controller: 'priceGroup'
        } )
        //other
        .state('settings', {
            url: "/settings",
            templateUrl: INGOT_ADMIN.partials + "/settings.html",
            controller: 'settings'
        } )
        .state('support', {
            url: "/support",
            templateUrl: INGOT_ADMIN.partials + "/support.html",
            controller: 'support'
        } )
        .state("otherwise",{
            url : '/',
            templateUrl: INGOT_ADMIN.partials + "/welcome.html",
            controller: 'welcome'
        })



});


/**
 * Click Tests
 *
 * @since 2.0.0
 */
//Controller for click groups list
ingotApp.controller( 'clickGroups', ['$scope', '$http', 'groupsFactory', function( $scope, $http,  groupsFactory ) {
    
    var page_limit = 10;
    
     groupsFactory.query({page: 1, limit: page_limit, context: 'admin'}, function(res){
	    
	    if( res.data.indexOf('No matching') > -1 ){
		    $scope.groups = {};
		    return;
	    };
	    
	    $scope.groups = JSON.parse( res.data );
	    
	    var total_groups = parseInt( res.headers['x-ingot-total'] );
	    total_pages = total_groups / page_limit;
	    $scope.total_pages = new Array( Math.round( total_pages ) );

    });
    
    $scope.paginate = function( page, $event ) {

	    if( jQuery('.paginate a.active').length ) {
            jQuery('.paginate a.active').toggleClass('active');
        }
        
	    jQuery( $event.currentTarget ).toggleClass('active');
	    
	    page = page + 1;
        groupsFactory.query({page: page, limit: page_limit, context: 'admin'}, function(res){
			if( res.data.indexOf('No matching groups found.') >= 0 ) { return; }
		    $scope.groups = JSON.parse( res.data );
	    });   
    }
    
}]);

//controller for deleting a group
ingotApp.controller( 'clickDelete', ['$scope', '$http', '$stateParams', '$state', function( $scope, $http, $stateParams, $state ){
    var groupID = $stateParams.groupID;
    if( 'undefined' == groupID ) {
        swal({
            title: INGOT_TRANSLATION.fail,
            text: INGOT_TRANSLATION.sorry,
            type: "error",
            confirmButtonText: INGOT_TRANSLATION.close
        });
        $state.go('clickTests.list' );
    }else{
        swal( {
            title: INGOT_TRANSLATION.are_you_sure,
            text: INGOT_TRANSLATION.delete_confirm,
            type: "warning",
            showCancelButton: true,
            confirmButtonText:INGOT_TRANSLATION.delete,
            cancelButtonText: INGOT_TRANSLATION.cancel,
            closeOnConfirm: false,
            closeOnCancel: false
        }, function ( isConfirm ) {
            if ( isConfirm ) {
                $http({
                    url: INGOT_ADMIN.api + 'groups/' + groupID + '?_wpnonce=' + INGOT_ADMIN.nonce,
                    method:'DELETE',
                    headers: {
                        'X-WP-Nonce': INGOT_ADMIN.nonce
                    }
                } ).success( function(){
                    swal( INGOT_TRANSLATION.deleted, "", "success" );
                    $scope.group = {};
                    $state.go('clickTests.list' );
                } ).error( function( data ) {
                    console.log( data );
                    $state.go('clickTests.list' );
                    swal({
                        title: INGOT_TRANSLATION.fail,
                        text: INGOT_TRANSLATION.sorry,
                        type: "error",
                        confirmButtonText: INGOT_TRANSLATION.close
                    });
                });

            } else{
                swal( INGOT_TRANSLATION.canceled, "", "success" );
                $state.go('clickTests.list' );
            }
        } );
    }

}]);

//controller for creating/editing a click group
ingotApp.controller( 'clickGroup', ['$scope', '$http', '$stateParams', '$rootScope', '$state', 'groupsFactory', function( $scope, $http, $stateParams, $rootScope, $state,  groupsFactory ) {
    var is_new = false;
    $scope.group_step = 1;
    $scope.new_group = false;
    $scope.click_type_options = INGOT_ADMIN.click_type_options;

    if( 'clickTests.new' == $state.current.name ) {
        is_new = true;
        $scope.new_group = true;
        $scope.group = {
            type: 'click',
            click_type_options : INGOT_ADMIN.click_type_options,
            variants: {},
            meta: {}
        };
    } else {
        $scope.group_step = 3;
        var groupID = $stateParams.groupID;
        groupsFactory.get({id: groupID}, function(res){
            if( 'array' == typeof res.meta ) {
                function toObject(arr) {
                    var rv = {};
                    for (var i = 0; i < arr.length; ++i){
                        if (arr[i] !== undefined) rv[i] = arr[i];
                    }
                    return rv;
                }
                res.meta = toObject( res.meta );
            }
	        $scope.group = res;
            $scope.choose_group_type($scope.group.sub_type);

		}, function(data, status, headers, config) {
            console.log( data );
            swal({
                title: INGOT_TRANSLATION.fail,
                text: INGOT_TRANSLATION.sorry,
                type: "error",
                confirmButtonText: INGOT_TRANSLATION.close
            });
        })

    }

    $scope.buttonStyle = function( id, type ) {
        var css = {};
	    if( type == 'button_color' ) {
		     css = {};
		    if( $scope.group.variants[id] && $scope.group.variants[id].meta && $scope.group.variants[id].meta.background_color ) {
                css[ 'background-color' ] = $scope.group.variants[ id ].meta.background_color;
            }

			if( $scope.group.variants[id] && $scope.group.variants[id].meta && $scope.group.variants[id].meta.color ) {
                css[ 'color' ] = $scope.group.variants[ id ].meta.color;
            }

		    return css;
	    } else {
            css = {};
            if( $scope.group.meta && $scope.group.meta.background_color ) {
                css[ 'background-color' ] = $scope.group.meta.background_color;
            }

            if( $scope.group.meta && $scope.group.meta.color ) {
                css[ 'color' ] = $scope.group.meta.color;
            }
            return css;
        }
	    
    };
    
    $scope.removeTest = function( index ) {
	    $scope.group.variants.splice( index, 1 );
	    return false;
    };

    $scope.submit = function( data ){
        var url;
        if( 'clickTests.new' == $state.current.name ) {
            url =INGOT_ADMIN.api + 'groups/?context=admin';
        }else{
            url = INGOT_ADMIN.api + 'groups/' + groupID + '?context=admin';
        }

        url +='&_wpnonce=' + INGOT_ADMIN.nonce;

        $http({
            method: 'POST',
            headers: {
                'X-WP-Nonce': INGOT_ADMIN.nonce
            },
            url: url,
            data: $scope.group
        } ).success(function(data) {
            $scope.group = data;

            if( true === is_new ) {
                is_new = false;
                $state.go( 'clickTests.edit', {groupID: $scope.group.ID} );
            }
            swal({
                title: INGOT_TRANSLATION.group_saved,
                text: '',
                type: "success",
                confirmButtonText: INGOT_TRANSLATION.close
            });
        } ).error(function(){
            swal({
                title: INGOT_TRANSLATION.fail,
                text: INGOT_TRANSLATION.sorry,
                type: "error",
                confirmButtonText: INGOT_TRANSLATION.close
            });
        })
    };

    $scope.addNewTest = function(e) {
        //make ID a random string so it will be treated as new by API
        var id = 'ingot_' + Math.random().toString(36).substring(7);
        if( !Array.isArray($scope.group.variants) ) {
            $scope.group.variants = [];
        }
        $scope.group.variants.push({'ID':id});
    };

    $scope.change_step = function( step ) {
        $scope.group_step = step;
    };

    $scope.partials_url = INGOT_ADMIN.partials;

    $scope.choose_group_type = function( type ) {
        $scope.group.sub_type = type;
    }

    $scope.has_type = function() {
        if( !$scope.group ) { return; }
        if( 'undefined' == $scope.group.sub_type || null == $scope.group.sub_type ){
            return false;
        }

        return true;
    }

}]);

//controller for group stats
ingotApp.controller( 'clickStats', ['$scope', '$http', '$stateParams', '$state', 'groupsFactory', function( $scope, $http, $stateParams, $state,  groupsFactory ) {

    var groupID = $stateParams.groupID;
    $scope.no_stats = INGOT_TRANSLATION.no_stats;
    $scope.group_id = groupID;

    if ( 'undefined' == groupID ) {

        swal( {
            title: INGOT_TRANSLATION.fail,
            text: INGOT_TRANSLATION.sorry,
            type: "error",
            confirmButtonText: INGOT_TRANSLATION.close
        } );
        $state.go( 'clickTests.list' );

    } else {

        $http({
            url: INGOT_ADMIN.api + 'groups/' + groupID + '/stats?_wpnonce=' + INGOT_ADMIN.nonce + '&context=admin',
            method:'GET',
            headers: {
                'X-WP-Nonce': INGOT_ADMIN.nonce
            }
        } ).success( function( res ){
            $scope.stats_exist = true;
            if( !Object.keys(res.variants).length ) {
                $scope.stats_exist = false;
                return;
            }

            $scope.stats = res;
            $scope.chart_data = {
                labels: [],
                datasets: [
                    {
                        label: [ INGOT_TRANSLATION.stats.c_rate ],
                        fillColor: "rgba(220,220,220,0.5)",
                        strokeColor: "rgba(220,220,220,0.8)",
                        highlightFill: "rgba(220,220,220,0.75)",
                        highlightStroke: "rgba(220,220,220,1)",
                        data: []
                    }
                ]
            }
            angular.forEach( $scope.stats.variants, function( variant, i ) {
                var name;

                if( 'undefined' !== variant.name ) {
                    name = variant.name;
                }else{
                    name = INGOT_TRANSLATION.stats.variant + ' ' + i;
                }
                $scope.chart_data.labels.push( name );
                var rate = Math.round( variant.conversion_rate * 100 ) / 100;
                $scope.chart_data.datasets[0].data.push( rate );
            });

            $scope.setChart( $scope.stats.group.average_conversion_rate );

        });

    }

    Chart.types.Bar.extend({
        name: 'BarOverlay',
        draw: function (ease) {

            // First draw the main chart
            Chart.types.Bar.prototype.draw.apply(this);

            var ctx = this.chart.ctx;
            var barWidth = this.scale.calculateBarWidth(this.datasets.length);

            for (var i = 0; i < this.options.verticalOverlayAtBar.length; ++i) {

                var overlayBar = this.options.verticalOverlayAtBar[i];

                // I'm hard-coding this to only work with the first dataset, and using a Y value that I know is maximum
                var x = this.scale.calculateBarX(this.datasets.length, 0, overlayBar);
                var y = this.scale.calculateY(overlayBar);

                var bar_base = this.scale.endPoint;

                ctx.beginPath();
                ctx.lineWidth = 2;
                ctx.strokeStyle = 'rgba(255, 0, 0, 1.0)';
                ctx.moveTo(100, y);
                ctx.lineTo(jQuery('#ingotChart').outerWidth(), y);
                ctx.stroke();
                ctx.font = "14px Arial";
                ctx.fillStyle = 'black';
                ctx.fillText('Group Average Conversion Rate (' + Math.round( overlayBar * 100  ) / 100 + '%)', jQuery('#ingotChart').outerWidth(), y - 10 )
            }
            ctx.closePath();
        }
    });

    $scope.setChart = function( avg ) {
        console.log( 'setting chart..' );

        var ctx = document.getElementById("ingotChart").getContext("2d");
        setTimeout(function(){
            var ingot_chart = new Chart(ctx).BarOverlay( $scope.chart_data, {
                scaleLabel: "          <%=value%>%",
                responsive: true,
                barValueSpacing: 10,
                verticalOverlayAtBar: [ avg ]
            } );
        }, 100);

    }


}]);

/**
 * Price Tests
 *
 * @since 2.0.0
 */
//Controller for price groups list
ingotApp.controller( 'priceGroups', ['$scope', '$http', 'groupsFactory', function( $scope, $http, groupsFactory ) {
    var page_limit = 10;
    groupsFactory.query({page: 1, limit: page_limit, context: 'admin', type: 'price' }, function(res){
		
		$scope.total_pages = false;
		$scope.groups = JSON.parse( res.data );
		if( res.headers['x-ingot-total'] ) {
			var total_groups = parseInt( res.headers['x-ingot-total'] );
			total_pages = total_groups / page_limit;
			$scope.total_pages = new Array( Math.round( total_pages ) );
		}

    });

    $scope.paginate = function( page, $event ) {
        if( jQuery('.paginate a.active').length ) {
            jQuery('.paginate a.active').toggleClass('active');
        }


        jQuery( $event.currentTarget ).toggleClass('active');

        page = page + 1;
        groupsFactory.query({page: page, limit: page_limit, context: 'admin'}, function(res){
            if( res.data.indexOf('No matching groups found.') >= 0 ) {
                return;
            }

            $scope.groups = JSON.parse( res.data );
        });
    }
    
}]);

//controller for creating/editing a price group
ingotApp.controller( 'priceGroup', ['$scope', '$http', '$stateParams', '$rootScope', '$state', 'groupsFactory', function( $scope, $http, $stateParams, $rootScope, $state, groupsFactory ) {

    $http({
        url: INGOT_ADMIN.api + 'products/plugins?_wpnonce=' + INGOT_ADMIN.nonce,
        method: 'GET',
        headers: {
            'X-WP-Nonce': INGOT_ADMIN.nonce
        }

    }).success( function( data, status, headers, config ) {
        $scope.plugins = data;
    } ).error( function ( data ) {
        console.log( data );
    } );


    if( 'priceGroup.new' == $state.current.name ) {
        $scope.group = {
            price_type_options : INGOT_ADMIN.price_type_options
        };
    }else{
        var groupID = $stateParams.groupID;
        groupsFactory.get({id: groupID}, function(res){
	        if( res[0] != 'N' && res[1] != 'o' ) {   
		        $scope.group = res;
	            $scope.products();
	        }
        }, function(data, status, headers, config) {
            console.log( data );
            swal({
                title: INGOT_TRANSLATION.fail,
                text: INGOT_TRANSLATION.sorry,
                type: "error",
                confirmButtonText: INGOT_TRANSLATION.close
            });
        })
    }

    $scope.submit = function( ){
        angular.forEach( $scope.group.tests, function( value, key ) {
	        value.default = parseFloat( value.default / 100 );	        
        });
        
         groupsFactory.save( $scope.group, function(res){
	        console.log( res);
	        $scope.group = res;
            if( 'priceTests.new' == $state.current.name ) {
                $state.go('priceTests.edit' ).toParams({
                    groupID: res.ID
                });
            }
            swal({
                title: INGOT_TRANSLATION.group_saved,
                text: '',
                type: "success",
                confirmButtonText: INGOT_TRANSLATION.close
            });
        }, function( error ) {
	        console.log( error );
	        swal({
                title: INGOT_TRANSLATION.fail,
                text: INGOT_TRANSLATION.sorry,
                type: "error",
                confirmButtonText: INGOT_TRANSLATION.close
            });
        })
		
    };

    $scope.addNewTest = function(e) {
        //make ID a random string so it will be treated as new by API
        var id = 'ingot_' + Math.random().toString(36).substring(7);
        if( !$scope.group.variants ) {
            $scope.group.variants = [];
        }

        $scope.group.variants.push({ 'ID': id, default: 0 });

		setTimeout( function() {
			jQuery(".slider-" + id).slider({
				value:0,
				min: -99,
				max: 99,
				slide: function( event, ui ) {
					$scope.group.variants[jQuery(event.target).data('index')].default = ui.value;
					jQuery(".slider-" + id + "-val").html( ui.value + '%' );
				}
			});
			$scope.$apply();
		}, 50 );
        
    };


    $scope.products = function() {
	    console.log( $scope.group );
        if( 'string' != typeof $scope.group.plugin){
            $scope.products = {};
        }
        $http({
            url: INGOT_ADMIN.api + 'products?plugin=' + $scope.group.plugin + '&_wpnonce=' + INGOT_ADMIN.nonce,
            method: 'GET',
            headers: {
                'X-WP-Nonce': INGOT_ADMIN.nonce
            }

        }).success( function( data, status, headers, config ) {
            $scope.products = data;
        } ).error( function ( data ) {
            console.log( data );
        } );
    }

}]);

/**
 * Support Controller
 *
 * @since 0.2.0
 */
ingotApp.controller( 'support', ['$scope', '$http', function( $scope, $http ) {
}]);

/**
 * Welcome Page Controller
 *
 * @since 0.2.0
 */
ingotApp.controller( 'welcome', ['$scope', '$http', function( $scope, $http ) {
    $scope.welcome = INGOT_TRANSLATION.welcome;

    //temporary for #61, #62 should make this uneed
    var url =  INGOT_ADMIN.api + 'settings?context=admin&_wpnonce=' + INGOT_ADMIN.nonce;
    $http({
        method: 'GET',
        url:url,
        headers: {
            'X-WP-Nonce': INGOT_ADMIN.nonce
        }

    }).success( function( data, status, headers, config ) {
        $scope.welcome.settings = data;
    }).error(function( data ){
        console.log( data );
    });

}]);


/**
 * Settings Controller
 *
 * @since 0.2.0
 */
ingotApp.controller( 'settings', ['$scope', '$http', function( $scope, $http ) {
    var url =  INGOT_ADMIN.api + 'settings?context=admin&_wpnonce=' + INGOT_ADMIN.nonce;
    $http({
        method: 'GET',
        url:url,
        headers: {
            'X-WP-Nonce': INGOT_ADMIN.nonce
        },

    }).success( function( data, status, headers, config ) {
        $scope.settings = data;
    }).error(function( data ){
        console.log( data );
    });

    $scope.submit = function(){
        $http({
            method: 'POST',
            headers: {
                'X-WP-Nonce': INGOT_ADMIN.nonce
            },
            url: url,
            data: $scope.settings
        } ).success(function(data) {
            $scope.settings =  data;
            swal({
                title: INGOT_TRANSLATION.settings_saved,
                text: '',
                type: "success",
                confirmButtonText: INGOT_TRANSLATION.close
            });
        } ).error(function(){
            swal({
                title: INGOT_TRANSLATION.fail,
                text: INGOT_TRANSLATION.sorry,
                type: "error",
                confirmButtonText: INGOT_TRANSLATION.close
            });
        })
    };

}]);

/**
 * Groups Factory
 *
 * @since 1.1.0
 * @since 0.2.0 as clickGroups
 */
ingotApp.factory( 'groupsFactory', function( $resource ) {

	return $resource( INGOT_ADMIN.api + 'groups/:id', {
		id: '@id',
        _wpnonce: INGOT_ADMIN.nonce,
        context: 'admin'
	},{
		'query' : {
			transformResponse: function( data, headers ) {
				var response = {
					data: data,
					headers: headers()
				}
				return response;
			}
		},
        'update':{
            method:'PUT',
            headers: {
                'X-WP-Nonce': INGOT_ADMIN.nonce
            }
        },
        'post':{
            method:'POST',
            headers: {
                'X-WP-Nonce': INGOT_ADMIN.nonce
            }
        },
        'save':{
            method:'POST',
            headers: {
                'X-WP-Nonce': INGOT_ADMIN.nonce
            }
        },
        'delete':{
            method:'DELETE',
            headers: {
                'X-WP-Nonce': INGOT_ADMIN.nonce
            }
        }
    })
	
});

