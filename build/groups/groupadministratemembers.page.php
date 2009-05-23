<?php

/**
 * This page allows to create a new group
 *
 */
class GroupMemberAdministrationPage extends GroupsBasePage
{
    protected function teaserContent()
    {
        $layoutkit = $this->layoutkit;
        $words = $layoutkit->getWords();
        // &gt; or &raquo; ?
        ?>
        <div id="teaser" class="clearfix">
        <div id="teaser_l1"> 
        <h1><a href="groups"><?= $words->get('Groups');?></a> &raquo; <a href=""><?= $words->get('GroupsAdministrateMembers');?></a></h1>
        </div>
        </div>
        <?php
    }

    protected function getSubmenuActiveItem()
    {
        return 'admin';
    }

    protected function column_col3()
    {
        // get translation module
        $layoutkit = $this->layoutkit;
        $words = $layoutkit->getWords();
        $model = $this->getModel();

        $members = $this->group->getMembers();
        $need_approval = $this->group->getMembers('WantToBeIn');
        $invited = $this->group->getMembers('Invited');

?>
    <div id="groups">
    <div class="subcolumns">
        <h3><?= $words->get('GroupsAdministrateMembers'); ?></h3>
        <div class="c50l">
            <div class="subcl">
        <h4><?= $words->get('GroupsCurrentMembers');?></h4>
        <table id='current_members'>
            <tr>
              <th colspan="2"><?= $words->get('Username');?></th>
              <th><?= $words->get('Action');?></th>
            </tr>
        <?php foreach ($members as $member) : ?>
            <tr>
                <td><?=MOD_layoutbits::linkWithPicture($member->Username) ?></td>
                <td><a href="people/<?= $member->Username; ?>" class="username"><?=$member->Username ?></a></td>
                <td><?= (($this->member->getPKValue() == $member->getPKValue()) ? '' : "<a class='ban' href='groups/{$this->group->getPKValue()}/banmember/{$member->getPKValue()}'>{$words->get('GroupsBanMember')}</a> / <a class='kick' href='groups/{$this->group->getPKValue()}/kickmember/{$member->getPKValue()}'>{$words->get('GroupsKickMember')}</a>");?></td>
            </tr>
        <?php endforeach; ?>
        </table>
        <script type='text/javascript'>
        var memberban = $('current_members').getElementsBySelector('a.ban');
        var memberkick = $('current_members').getElementsBySelector('a.kick');
        memberban.each(function(elem){
            elem.observe('click', function(e){
                if (!confirm('<?= $words->get('GroupsConfirmMemberBan');?>'))
                {
                    Event.stop(e);
                }
            })
        });
        memberkick.each(function(elem){
            elem.observe('click', function(e){
                if (!confirm('<?= $words->get('GroupsConfirmMemberKick');?>'))
                {
                    Event.stop(e);
                }
            })
        });
        </script>
            </div> <!-- subcl -->
        </div> <!-- c62l -->

        <div class="c50r">
<?php if ($this->group->Type != 'Public') :?>
            <div class="subcl">
                <h4><?= $words->get('GroupsProspectiveMembers');?></h4>
                <table id='possible_members'>
                    <tr>
                      <th colspan="2"><?= $words->get('Username');?></th>
                      <th><?= $words->get('Action');?></th>
                    </tr>
                <?php foreach ($need_approval as $member) : ?>
                    <tr>
                        <td><?=MOD_layoutbits::linkWithPicture($member->Username) ?></td>
                        <td><a href="people/<?=$member->Username ?>" class="username"><?=$member->Username ?></a></td>
                        <td><?= (($this->member->getPKValue() == $member->getPKValue()) ? '' : "<a class='accept' href='groups/{$this->group->getPKValue()}/acceptmember/{$member->getPKValue()}'>Accept?</a>");?></td>
                    </tr>
                <?php endforeach; ?>
                </table>

            </div> <!-- subcl -->
<?php endif ;?>
            <div class='subcl'>
                <h4><?= $words->get('GroupsInvitedMembers');?></h4>
                <table id='invited_members'>
                    <tr>
                      <th colspan="2"><?= $words->get('Username');?></th>
                    </tr>
<?php if ($invited) : ?>
    <?php foreach ($invited as $member) : ?>
                    <tr>
                        <td><?=MOD_layoutbits::linkWithPicture($member->Username) ?></td>
                        <td><a href="people/<?=$member->Username ?>" class="username"><?=$member->Username ?></a></td>
                    </tr>
    <?php endforeach; ?>
<?php endif; ?>
                </table>
            </div>
            <div class='subcl'>
                <h4><?= $words->get('GroupsInviteMember') ?></h4>
                <div id='search_result' style='display: none;padding: 3px; margin-bottom: 3px'></div>
                <form method='get' action='groups/<?= $this->group->getPKValue(); ?>/invitemembers/search' id='invite_form'>
                    <input type='text' value='Enter username' name='username' id='search_username'/><input type='submit' value='<?= $words->get('Search');?>' id='search_username_submit'/>
                </form>
            </div>
            <script type='text/javascript'>
                var search_handler = {
                    display_result: function(member_object){
                        var search_div = $('search_result');
                        search_div.innerHTML = '';
                        search_div.style.border = '1px solid black';
                        search_div.style.display = 'block';
                        search_div.style.backgroundColor = '#ffffff';
                        var counter = 0;
                        for (var m in member_object)
                        {
                            var a = document.createElement('a');
                            a.href = '';
                            a.id = 'invite_member_' + member_object[m];
                            a.title = '<?= $words->get('GroupsClickToSendInvite');?>' + m;
                            a.appendChild(document.createTextNode('<?= $words->get('GroupsInvite');?>' + m));
                            $(a).observe('click',function(e){
                                e = e || window.event;
                                search_handler.add_invite(e);
                                Event.stop(e);
                            });
                            search_div.appendChild(a);
                            search_div.appendChild(document.createElement('br'));
                            counter++;
                        }
                        if (counter == 0)
                        {
                            search_div.appendChild(document.createTextNode('<?= $words->get('GroupsCouldNotFindMembers');?>'));
                        }
                    },
                    add_invite: function(e){
                        var it = e.target || e.srcElement;
                        var id = it.id.substr(14);
                        var ajax = new Ajax.Request('groups/<?= $this->group->getPKValue(); ?>/invitememberajax/' + id, {
                            method: 'get',
                            onSuccess: function(transport){
                                if (transport.responseText == 'success')
                                {
                                    search_handler.add_invite_callback(it);
                                }
                                else
                                {
                                    alert('<?= $words->get('GroupsCouldNotInvite');?>');
                                }
                            },
                            onFailure: function(transport){
                                alert('<?= $words->get('GroupsInviteFailedTechError');?>');
                            }
                        });
                    },
                    add_invite_callback: function(it){
                        var invited = it.firstChild.data.substr(7);
                        var tr = document.createElement('tr');
                        var td = document.createElement('td');
                        td.appendChild(document.createTextNode(invited + '<?= $words->get('GroupsHasBeenInvited');?>'));
                        td.setAttribute('colspan', 2);
                        tr.appendChild(td);
                        $('invited_members').tBodies[0].appendChild(tr);
                        $(it).remove();
                    }

                };
                $('search_username').observe('focus', function(e){
                    if ($('search_username').value == '<?= $words->get('GroupsEnterUsername');?>')
                    {
                        $('search_username').value = '';
                    }
                });
                $('invite_form').observe('submit', function(e){
                    e = e || window.event;
                    var ajax = new Ajax.Request('groups/<?= $this->group->getPKValue(); ?>/membersearch/' + $('search_username').value, {
                        method: 'get',
                        onSuccess: function(transport){
                            var result = ((transport.responseText != '[]') ? transport.responseText.evalJSON() : {});
                            search_handler.display_result(result);
                        },
                        onFailure: function(transport){
                            alert('<?= $words->get('GroupsInviteFailedTechError');?>');
                        }
                    });
                    Event.stop(e)
                });
            </script>
        </div> <!-- c50r -->
    </div> <!-- subcolums -->

</div>
    <?php
    }


}

?>
