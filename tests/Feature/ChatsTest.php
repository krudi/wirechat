<?php

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chats as Chatlist;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;


///Auth checks 
it('checks if users is authenticated before loading chatlist', function () {
    Livewire::test(Chatlist::class)
        ->assertStatus(401);
});


test('authenticaed user can access chatlist ', function () {
    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(Chatlist::class)
        ->assertStatus(200);
});


///Content validations
it('has "chats label set in chatlist"', function () {
    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(Chatlist::class)
        ->assertSee('Chat');
});


it('doesnt shows search field if search is disabled in wirechat.config:tesiting Search placeholder', function () {

    Config::set("wirechat.allow_chats_search", false);

    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(Chatlist::class)
                      ->assertDontSee('Search my conversations');
});



it('shows search field if search is enabled in wirechat.config:tesiting Search placeholder', function () {

    Config::set("wirechat.allow_chats_search", true);

    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(Chatlist::class)
        ->assertSee('Search');
});




describe('Chatlist', function () {


    it('shows label "No conversations yet" items when user does not have chats', function () {

        $auth = User::factory()->create();

  


        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('No conversations yet');
    });


    it('loads conversations items when user has them', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);


        //create conversation with user1
        $auth->createConversationWith($user1,'hello');

        //create conversation with user2
        $auth->createConversationWith($user2,'new message');


        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('iam user 1')
            ->assertSee('iam user 2')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 2;
            });
    });



    it('shows suffix (You) if user has a self conversation', function () {

        $auth = User::factory()->create(['name' => 'Test']);

        //create conversation with user1
        $auth->createConversationWith($auth,'hello');


        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('(You)')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 1;
            });
    });


    it('does not load blank conversations(where not even deleted messages exists)', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);


        //create BLANK conversation with user1
        $auth->createConversationWith($user1);

        //create conversation with user2
        $auth->createConversationWith($user2,'new message');


        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertDontSee('iam user 1') //Blank conversation should not load
            ->assertSee('iam user 2')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 1;
            });
    });


    it('does not load deleted conversations by user', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);


        //create conversation with user1
        $auth->createConversationWith($user1,'nothing');

        //create conversation with user2
        $conversationToBeDeleted=   $auth->createConversationWith($user2,'nothing 2');

        //!now delete conversation with user 2
        $auth->deleteConversation($conversationToBeDeleted);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('iam user 1')
            ->assertDontSee('iam user 2')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 1;
            });
    });


    it('it shows last message and lable "you:" if it exists in chatlist', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');


        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('How are you doing')
            ->assertSee('You:');
    });

    it('it doesnt show label "you:" if last message doenst belong to auth', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        //here we delay the create messsage so that we can NOT have both messages with the same timestamp
        //now let's send message to auth 
        $user1->sendMessageTo($auth, message: 'I am good');



        // dd($conversations,$messages);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('I am good') //see message
            ->assertDontSee('You:'); //assert not visible
    });

    it('shows unread message count "2" if message does not belong to user', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        //here we delay the create messsage so that we can NOT have both messages with the same timestamp
        //now let's send message to auth 
        $user1->sendMessageTo($auth, message: 'I am good');
        $user1->sendMessageTo($auth, message: 'kudos');



        // dd($conversations,$messages);

        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('2'); //
    });

    it('shows date/time message was created', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
       $conversation=  $auth->createConversationWith($user1);

        //manually create message so that we can adjust the time to 3 weeks ago
       Message::create([
        'conversation_id' => $conversation->id,
        'sendable_type' => get_class($auth),  
        'sendable_id' =>$auth->id, 
        'body' => "How are you doing"
        ]);


        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('1s');
    });

    it('it shows attatchment lable if message contains file or image', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
       $conversation=  $auth->createConversationWith($user1);

       $createdAttachment = Attachment::factory()->create();

        //manually create message so we can attach attachment id
       Message::create([
        'conversation_id' => $conversation->id,
        'sendable_type' => get_class($auth),  
        'sendable_id' =>$auth->id, 
        'attachment_id'=>$createdAttachment->id
        ]);



        Livewire::actingAs($auth)->test(Chatlist::class)
            ->assertSee('📎 Attachment');
    });

});


describe('Search', function () {


    it('it shows all conversations items when search query is null', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'John']);
        $user2 = User::factory()->create(['name' => 'Mary']);


        //create conversation with user1
        $auth->createConversationWith($user1,'hello');

        //create conversation with user2
        $auth->createConversationWith($user2,'how are you doing');


        Livewire::actingAs($auth)->test(Chatlist::class, ['search' => null])
            ->assertSee('John')
            ->assertSee('Mary')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 2;
            });
    });

    it('can filter conversations when search query is filled', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'John']);
        $user2 = User::factory()->create(['name' => 'Mary']);


        //create conversation with user1
        $auth->createConversationWith($user1,'hello');

        //create conversation with user2
        $auth->createConversationWith($user2,'how are you doing');


        Livewire::actingAs($auth)->test(Chatlist::class)
            ->set('search','John')
            ->assertSee('John')
            ->assertViewHas('conversations', function ($conversations) {
                return count($conversations) == 1;
            });
    });

});