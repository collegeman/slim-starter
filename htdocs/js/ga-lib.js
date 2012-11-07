!function($, B) {

  "use strict";

  if (!window.console) window.console = { log: function() {}, error: function() {} };

  var main, accountsNav, profilesNav, deckView;

  var selectedAccount;

  var DeckView = B.View.extend({
    initialize: function() {
      this.$('li.profile').height( this.$el.height() - 57 );
    }
  });

  var AccountsNavItemView = B.View.extend({
    tagName: 'li',
    events: {
      'click': function(e) {
        selectedAccount = this.model;
        profilesNav.collection.fetch();
      }
    },
    initialize: function() {
      var that = this;
      this.model.on('change:hidden', function() {
        that.$el.toggle(!that.model.get('hidden'));
      });
    },
    render: function() {
      this.$el.html( '<a href="#" data-dismiss="accounts"><span>' + this.model.get('name') + '</span><i class="pull-right icon-chevron-right icon-white"></i></a>' );
      return this;
    }
  });

  var AccountsNavView = B.View.extend({
    events: {
      'keydown .search-query': _.debounce(function(e) {
        var keyword = this.$el.find('.search-query').val().trim().toLowerCase();
        this.collection.each(function(account) {
          account.set('hidden', account.get('name').toLowerCase().indexOf(keyword) === -1);
        });
      }, 100)
    },
    initialize: function() {
      var that = this;

      var collection = this.collection = new (B.Collection.extend({
        url: '/api/accounts'
      }));
      
      this.$el.height( $(window).height() - parseInt(this.$el.css('padding-top')) );

      $(window).resize(function() {
        that.$el.height( $(window).height() - parseInt(that.$el.css('padding-top')) );
      });

      this.$list = this.$('ul');
      this.collection.on('reset', _.bind(this.render, this));
      this.collection.fetch({
        success: function() {
          selectedAccount = collection.first();
          profilesNav.collection.fetch();
        }
      });
    },
    render: function() {
      var $list = this.$list.html('');
      this.collection.each(function(account) {
        $list.append( new AccountsNavItemView({ model: account }).render().$el );
      });
      return this;
    }
  });

  var Profile = B.Model.extend({
    url: function() {
      return '/api/profile/' + (this.id || '');
    }
  });

  var ProfilesNavItemView = B.View.extend({
    tagName: 'li',
    events: {
      'click': function(e) {
        var profile = new Profile({ id: this.model.id });
        profile.fetch({
          success: function() {
            console.log(profile);
          }
        });
        return false;
      }
    },
    initialize: function() {
      var that = this;
      this.model.on('change:hidden', function() {
        that.$el.toggle(!that.model.get('hidden'));
      });
    },
    render: function() {
      this.$el.html( '<a href="#" title="' + this.model.get('name') + '"><span>' + this.model.get('name') + '</span><i class="pull-right icon-chevron-right icon-white"></i></a>' );
      return this;
    }
  });

  var ProfilesNavView = B.View.extend({
    events: {
      'keydown .search-query': _.debounce(function(e) {
        var keyword = this.$el.find('.search-query').val().trim().toLowerCase();
        this.collection.each(function(profile) {
          profile.set('hidden', profile.get('name').toLowerCase().indexOf(keyword) === -1);
        });
      }, 100)
    },
    initialize: function() {
      var that = this;

      this.$el.height( $(window).height() - parseInt(this.$el.css('padding-top')) );

      $(window).resize(function() {
        that.$el.height( $(window).height() - parseInt(that.$el.css('padding-top')) );
      });

      this.collection = new (B.Collection.extend({
        url: function() {
          return '/api/profiles/' + (selectedAccount ? selectedAccount.id : '');
        }
      }));
      this.$list = this.$('ul');
      this.collection.on('reset', _.bind(this.render, this));

      this.$el.hammer().on('drag', function(e) {
        if (e.direction === 'left') {
          e.preventDefault();
          return false;
        }
      });
    },
    render: function() {
      var $list = this.$list.html('');
      this.collection.each(function(profile) {
        $list.append( new ProfilesNavItemView({ model: profile }).render().$el );
      });
    }
  });

  var MainView = B.View.extend({
    events: {
      'click [data-toggle="profiles"]': function(e) {
        this.$el.toggleClass('profiles-open');
        this.$el.removeClass('accounts-open');
        return false;
      },
      'click [data-toggle="accounts"]': function(e) {
        this.$el.toggleClass('accounts-open');
        return false;
      },
      'click [data-dismiss="profiles"]': function(e) {
        this.$el.removeClass('profiles-open').removeClass('accounts-open');
        return false;
      },
      'click [data-dismiss="accounts"]': function(e) {
        this.$el.removeClass('accounts-open');
        return false;
      }
    }
  });

  $(function() {
    accountsNav = new AccountsNavView({ el: $('#account-nav') });
    profilesNav = new ProfilesNavView({ el: $('#profile-nav') });
    deckView = new DeckView({ el: $('#deck') });
    main = new MainView({ el: $('body') });
  });

}(jQuery, Backbone);