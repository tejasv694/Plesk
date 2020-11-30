// Copyright 1999-2017. Plesk International GmbH. All rights reserved.

define(function () {
    var ce = Jsw.createElement;

    return Class.create(Jsw.ActiveList, {
        _initConfiguration: function ($super, config) {
            $super(config);

            this.itemActions.delete = this.delete.bind(this);
        },

        _addEvents: function ($super) {
            $super();

            this.data.forEach(function (item) {
                var el = this.getItemElement(item);

                el.select('.caption-service-toolbar .item-visible .toggler').each(function (element) {
                    element.observe('click', function (e) {
                        var editBlock = this.up('.caption-service-toolbar').down('.item-invisible');
                        var createBlock = this.up('.item-visible');
                        editBlock.removeClassName('item-invisible').addClassName('item-visible');
                        createBlock.removeClassName('item-visible').addClassName('item-invisible');

                        Event.stop(e);
                    });
                });

                el.select('.js-webadmin').each(function (element) {
                    element.observe('click', function () {
                        var select = element.up('div').down('select');
                        var selected = select.options[select.selectedIndex];
                        var userLogin = selected.readAttribute('data-user-login');
                        var passwordPrompt = selected.readAttribute('data-password-prompt');
                        var domainId = element.up('.active-list-item').down('.js-domain-id').value;
                        var params = { domainId: domainId };

                        if (userLogin) {
                            // eslint-disable-next-line no-alert
                            var dbPassword = prompt(passwordPrompt);
                            if (!dbPassword) {
                                return;
                            }
                            params.dbPassword = dbPassword;
                        }

                        Jsw.redirect({
                            url: selected.value,
                            method: params.dbPassword ? 'post' : 'get',
                            data: params,
                            target: 'dbedit',
                        });
                    });
                });
            }.bind(this));
        },

        itemCaptionHeadView: function ($super, item) {
            var view = $super(item);

            if (item.customDescription && Object.keys(item.customDescription).length) {
                view.children.push(ce('.caption-description',
                    ce('.caption-description-wrap',
                        Object.keys(item.customDescription).map(function (type, index) {
                            return ce('.description-item',
                                ce('span.description-item-text',
                                    (index > 0 ? this.lmsg(type + 'Description') + ': ' : ''),
                                    item.customDescription[type].truncate(50).escapeHTML(),
                                    ce('span.tooltipData', item.customDescription[type].escapeHTML())
                                ),
                                ce('span.description-item-buttons',
                                    ce('a.toggler', { onclick: this.editDescription.bind(this, item, type) }, this.lmsg('buttonDescriptionEdit')),
                                    ce('a.toggler', { onclick: this.removeDescription.bind(this, item, type) }, this.lmsg('buttonDescriptionRemove'))
                                )
                            );
                        }.bind(this))
                    )
                ));
            }


            var summaryItems = [];
            if (item.hostingType === 'vrt_hst') {
                var webrootDir = [
                    ce('i.icon', ce('img', { src: this.icons['website-folder'] })),
                    ' ',
                    ce('span', item.webrootDir + '/'),
                ];
                if (item.filemanagerUrl) {
                    webrootDir = ce('a.i-link', { href: Jsw.prepareUrl(item.filemanagerUrl) },
                        webrootDir,
                        ce('span.tooltipData', this.lmsg('websiteAtHint'))
                    );
                }
                summaryItems.push([this.lmsg('websiteAt') + ' ', ce('b', webrootDir)]);

                if (item.purchaseDomainMessage) {
                    summaryItems.push(item.purchaseDomainMessage);
                }

                var ipAddress = item.ipv4Address && item.ipv6Address
                    ? item.ipv4Address + '(v4), ' + item.ipv6Address + '(v6)'
                    : item.ipv4Address || item.ipv6Address;
                summaryItems.push(this.lmsg('ipAddress', { ipAddress: '<b>' + ipAddress + '</b>' }));

                summaryItems.push(this.lmsg('sysUser', { sysUser: '<b>' + item.sysUser.escapeHTML() + '</b>' }));
            } else if (item.hostingType === 'none') {
                summaryItems.push([
                    this.lmsg('noHosting'),
                    item.showAddHostingButton ? [' ', ce('a', { href: Jsw.prepareUrl(item.changeHostingUrl) }, '[' + this.lmsg('changeHosting') + ']')] : '',
                ]);
            } else if (item.hostingType === 'frm_fwd' || item.hostingType === 'std_fwd') {
                summaryItems.push([
                    this.lmsg('forwardingTo') + ' ',
                    ce('a[target=_blank]', { href: Jsw.prepareUrl(item.forwardingUrl) }, item.forwardingDisplayUrl.escapeHTML()),
                ]);
            } else if (item.isDomainAlias) {
                summaryItems.push(item.description);
            }

            if (item.icpStatus !== undefined) {
                var icpPermitLocale = Jsw.Locale.getSection('components.icp-permit');
                summaryItems.push(
                    icpPermitLocale.lmsg('icpPermit') + ': ',
                    ce('span.tooltipData', icpPermitLocale.lmsg(item.icpStatus ? 'icpStatusAllowedDescription' : 'icpStatusNotAllowedDescription')),
                    ce(this.urls.icpPermitLearnMore ? 'a.i-link[target=_blank][href=' + this.urls.icpPermitLearnMore + ']' : 'b',
                        ce('i.icon', ce('img[alt=""]', { src: this.icons[item.icpStatus ? 'subscription-status-ok' : 'att-tr'] })), ' ',
                        item.icpPermit ? item.icpPermit.escapeHTML() : icpPermitLocale.lmsg('icpStatusNotAllowed')
                    )
                );

                if (item.isIcpChangeable) {
                    summaryItems.push(
                        ce('a', { onclick: this.icpPermit.bind(this, item) }, icpPermitLocale.lmsg(item.icpPermit ? 'changeIcpPermit' : 'enterIcpPermit'))
                    );

                    summaryItems.push(ce('a', {
                        onclick: function () {
                            Jsw.redirectPost('/smb/web/icp-permit', {
                                id: item.id,
                                status: item.icpStatus ? 'false' : 'true',
                            });
                        },
                    }, icpPermitLocale.lmsg(item.icpStatus ? 'revokeIcpStatus' : 'grantIcpStatus')));
                }
            }

            view.children.push(ce('.caption-summary',
                ce('.caption-summary-wrap',
                    summaryItems.map(function (summaryItem) {
                        return ce('span.summary-item', summaryItem);
                    })
                )
            ));

            var settingsUrl = item.isDomainAlias
                ? '/domain-alias/settings/id/' + item.aliasId
                : '/web/settings/id/' + item.domainId;

            view.children.push(ce('.caption-toolbar',
                ce('.caption-toolbar-wrap',
                    ce('a.i-link', { href: Jsw.prepareUrl(settingsUrl) },
                        ce('i.icon', ce('img[alt=""]', { src: this.icons.customize })), ' ',
                        ce('span', this.lmsg('hostingSettings')),
                        ce('span.tooltipData', this.lmsg('hostingSettingsHint'))
                    ),
                    ce('a.i-link[target=_blank]', { href: item.siteUrl.escapeHTML() },
                        ce('i.icon', ce('img[alt=""]', { src: this.icons.publish })), ' ',
                        ce('span', this.lmsg('openSite')),
                        ce('span.tooltipData', this.lmsg('openSiteHint'))
                    ),
                    item.previewUrl ? ce('a.i-link[target=_blank]', { href: item.previewUrl },
                        ce('i.icon', ce('img[alt=""]', { src: this.icons.preview })), ' ',
                        ce('span', this.lmsg('previewSite')),
                        ce('span.tooltipData', this.lmsg('previewSiteHint'))
                    ) : '',
                    item.isSuspended && item.error503PageUrl ? ce('a', { href: Jsw.prepareUrl(item.error503PageUrl) },
                        ce('span', this.lmsg('error503PageLink'))
                    ) : '',
                    ce('span.separator', ce('span')),
                    Object.keys(item.changeStatusLinks).map(function (title) {
                        return ce('a.js-button-' + title, {
                            onclick: function () {
                                Jsw.redirectPost(item.changeStatusLinks[title]);
                            },
                        }, this.lmsg(title), ce('span.tooltipData', this.lmsg(title + 'Hint')));
                    }.bind(this)),
                    item.selfDescriptionType ? [
                        ce('span.separator', ce('span')),
                        item.customDescription[item.selfDescriptionType]
                            ? ''
                            : ce('a', { onclick: this.editDescription.bind(this, item, item.selfDescriptionType) }, this.lmsg('buttonDescription')),
                    ] : ''
                )
            ));

            return view;
        },

        itemServiceQuickStartBlockView: function (service) {
            return ce('.quick-start-block',
                ce('.quick-start-name', service.name),
                ce('.quick-start-description', service.description),
                ce('.quick-start-actions',
                    service.actions.map(function (action) {
                        return ce('a.btn',
                            {
                                href: action.href,
                                target: action.newWindow ? '_blank' : null,
                            },
                            ce('i.icon', ce("img[alt='']", { src: action.icon })),
                            ' ' + action.title
                        );
                    })
                )
            );
        },

        itemServiceDatabaseBlockView: function (service) {
            return ce('.caption-service-block',
                ce('input.js-domain-id[type=hidden]', { value: service.domainId }),
                ce('span.caption-service-title',
                    ce('i.caption-service-icon', ce('a[href=#]', ce('img', { src: service.icon.escapeHTML(), alt: service.title.escapeHTML() }))),
                    ce('span.caption-service-name', ce('a', { href: service.href.escapeHTML() }, service.title.escapeHTML()))
                ),
                ce('.caption-service-toolbar',
                    service.create ? ce('.caption-service-item.item-visible',
                        ce('a.btn', { href: service.create.button.href.escapeHTML() }, service.create.button.title.escapeHTML()),
                        service.create.toggler ? ce('span.caption-service-text',
                            service.create.togglerText.escapeHTML() + ' ',
                            ce('a.toggler', service.create.toggler.escapeHTML())
                        ) : ''
                    ) : '',
                    service.edit ? ce('.caption-service-item.' + service.edit.class,
                        ce('select',
                            service.edit.databases.map(function (database) {
                                return ce('option', database.attributes, database.title);
                            })
                        ),
                        ce('span.btn',
                            ce('button.js-webadmin[type=button]', service.edit.button.title)
                        )
                    ) : ''
                )
            );
        },

        getItemOverviewUrl: function (item) {
            return Jsw.prepareUrl('/web/overview/id/' + item.id);
        },

        editDescription: function (item, type) {
            var adminInfoLocale = Jsw.Locale.getSection('components.forms.admin-info');
            var buttonsLocale = Jsw.Locale.getSection('components.buttons');
            new Jsw.CustomDescription.PopupForm({
                id: 'editDescription',
                cls: 'popup-panel',
                hint: adminInfoLocale.lmsg('popupHint' + type.capitalize()),
                value: item.customDescription[type],
                locale: {
                    popupTitle: this.lmsg('popupDescriptionTitle', {
                        descriptionType: adminInfoLocale.lmsg('description' + type.capitalize()),
                        name: item.displayName.escapeHTML(),
                    }),
                    buttonOk: buttonsLocale.lmsg('save'),
                    buttonCancel: buttonsLocale.lmsg('cancel'),
                },
                handler: function (value) {
                    Jsw.redirectPost(this.urls['edit-description'], {
                        id: item.id.substr(2),
                        type: type,
                        description: value,
                    });
                }.bind(this),
            });
        },

        removeDescription: function (item, type) {
            var buttonsLocale = Jsw.Locale.getSection('components.buttons');
            Jsw.messageBox.show({
                type: Jsw.messageBox.TYPE_YESNO,
                buttonTitles: {
                    yes: buttonsLocale.lmsg('yes'),
                    no: buttonsLocale.lmsg('no'),
                    wait: buttonsLocale.lmsg('wait'),
                },
                text: this.lmsg('confirmOnDeleteDescription'),
                onYesClick: function () {
                    Jsw.redirectPost(this.urls['edit-description'], {
                        id: item.id.substr(2),
                        type: type,
                        description: '',
                    });
                }.bind(this),
            });
        },

        icpPermit: function (item) {
            var buttonsLocale = Jsw.Locale.getSection('components.buttons');
            var icpPermitLocale = Jsw.Locale.getSection('components.icp-permit');

            var form = new Jsw.PopupForm({
                cls: 'popup-panel',
            });
            form.setBoxType('form-box');
            form.setTitle(icpPermitLocale.lmsg(item.icpPermit ? 'changeFormTitle' : 'enterFormTitle', { domainName: item.displayName.escapeHTML() }));
            Jsw.render($(form._contentAreaId), ce('.form-row',
                ce('.field-name',
                    ce('label[for=icp-permit-' + item.id + ']', icpPermitLocale.lmsg('icpPermit'))
                ),
                ce('.field-value',
                    ce('input#icp-permit-' + item.id + '.input-text.f-middle-size[type=text]', { value: item.icpPermit })
                )
            ));
            var buttonOk = form.addRightButton(buttonsLocale.lmsg('save'), function () {
                this._updateButton(buttonOk, { disabled: true });
                Jsw.redirectPost(Jsw.prepareUrl('/smb/web/icp-permit'), {
                    id: item.id,
                    permit: $('icp-permit-' + item.id).getValue(),
                });
            }, true, true);
            form.addRightButton(buttonsLocale.lmsg('cancel'), function () {
                this.hide();
            }, false, false);
        },

        delete: function (item) {
            var confirmDeleteText;
            if (item.isDomainAlias) {
                confirmDeleteText = this.lmsg('buttonDeleteAliasConfirmationDescription');
            } else if (item.isSubdomain) {
                confirmDeleteText = this.lmsg('buttonDeleteSubdomainConfirmationDescription');
            } else {
                confirmDeleteText = this.lmsg('buttonDeleteDomainConfirmationDescription');
            }

            var buttonsLocale = Jsw.Locale.getSection('components.buttons');
            Jsw.messageBox.show({
                type: Jsw.messageBox.TYPE_YESNO,
                buttonTitles: {
                    yes: buttonsLocale.lmsg('yes'),
                    no: buttonsLocale.lmsg('no'),
                },
                text: confirmDeleteText,
                subtype: 'delete',
                onYesClick: function () {
                    Jsw.redirectPost('/web/delete', {
                        ids: {
                            0: item.id,
                        },
                        redirect: true,
                    });
                },
            });
        },

        showInformer: function () {
            require(['app/domain/help'], function (Informer) {
                new Informer();
            });
        },
    });
});
