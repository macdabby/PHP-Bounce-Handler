<?php

/* BOUNCE HANDLER Class, Version 7.3
 * Description: "chops up the bounce into associative arrays"
 *     ~ http://www.anti-spam-man.com/php_bouncehandler/v7.3/
 *     ~ https://github.com/cfortune/PHP-Bounce-Handler/
 *     ~ http://www.phpclasses.org/browse/file/11665.html
 */

/* Debugging / Contributers:
    * "Kanon"
    * Jamie McClelland http://mayfirst.org
    * Michael Cooper
    * Thomas Seifert
    * Tim Petrowsky http://neuecouch.de
    * Willy T. Koch http://apeland.no
    * ganeshaspeaks.com - FBL development
    * Richard Catto - FBL development
    * Scott Brynen - FBL development  http://visioncritical.com
*/

namespace cfortune\PHPBounceHandler

class BounceHandler {

    // Properties
    public $head_hash = array();
    public $fbl_hash = array();
    public $body_hash = array(); // not necessary

    public $looks_like_a_bounce = false;
    public $looks_like_an_FBL = false;
    public $looks_like_an_autoresponse = false;
    public $is_hotmail_fbl = false;

    // these are for feedback reports, so you can extract uids from the emails
    // eg X-my-custom-header: userId12345
    // eg <img src="http://mysite.com/track.php?u=userId12345">
    public $web_beacon_preg_1 = "";
    public $web_beacon_preg_2 = "";
    public $x_header_search_1 = "";
    public $x_header_search_2 = "";

    // accessors
    public $type = "";
    public $web_beacon_1 = "";
    public $web_beacon_2 = "";
    public $feedback_type = "";
    public $x_header_beacon_1 = "";
    public $x_header_beacon_2 = "";

    // these accessors are useful only for FBL's
    // or if the output array has only one index
    public $action = "";
    public $status = "";
    public $subject = "";
    public $recipient = "";

    // the raw data set, a multiArray
    public $output = array();

    public $bouncelist = array(
        '[45]\d\d[- ]#?([45]\.\d\.\d)'                              => 'x',         # use the code from the regex
        'Diagnostic[- ][Cc]ode: smtp; ?\d\d\ ([45]\.\d\.\d)'        => 'x',         # use the code from the regex
        'Status: ([45]\.\d\.\d)'                                    => 'x',         # use the code from the regex

        'not yet been delivered'                                    => '4.2.0',     #
        'Message will be retried for'                               => '4.2.0',     #

        'Benutzer hat zuviele Mails auf dem Server'                 => '4.2.2',     #.DE "mailbox full"
        'exceeded storage allocation'                               => '4.2.2',     #
        'Mailbox full'                                              => '4.2.2',     #
        'mailbox is full'                                           => '4.2.2',     #BH
        'Mailbox quota usage exceeded'                              => '4.2.2',     #BH
        'Mailbox size limit exceeded'                               => '4.2.2',     #
        'over ?quota'                                               => '4.2.2',     #
        'quota exceeded'                                            => '4.2.2',     #
        'Quota violation'                                           => '4.2.2',     #
        'User has exhausted allowed storage space'                  => '4.2.2',     #
        'User has too many messages on the server'                  => '4.2.2',     #
        'User mailbox exceeds allowed size'                         => '4.2.2',     #
        'mailfolder is full'                                        => '4.2.2',     #
        'user has Exceeded'                                         => '4.2.2',     #
        'not enough storage space'                                  => '4.2.2',     #

        'Delivery attempts will continue to be made for'            => '4.3.2',     #SB: 4.3.2 is a more generic 'defer'; Kanon added. From Symantec_AntiVirus_for_SMTP_Gateways@uqam.ca Im not sure why Symantec delayed this message, but x.2.x means something to do with the mailbox, which seemed appropriate. x.5.x (protocol) or x.7.x (security) also seem possibly appropriate. It seems a lot of times its x.5.x when it seems to me it should be x.7.x, so maybe x.5.x is standard when mail is rejected due to spam-like characteristics instead of x.7.x like I think it should be.
        'delivery temporarily suspended'                            => '4.3.2',     #
        'Greylisted for 5 minutes'                                  => '4.3.2',     #
        'Greylisting in action'                                     => '4.3.2',     #
        'Server busy'                                               => '4.3.2',     #
        'server too busy'                                           => '4.3.2',     #
        'system load is too high'                                   => '4.3.2',     #
        'temporarily deferred'                                      => '4.3.2',     #
        'temporarily unavailable'                                   => '4.3.2',     #
        'Throttling'                                                => '4.3.2',     #
        'too busy to accept mail'                                   => '4.3.2',     #
        'too many connections'                                      => '4.3.2',     #
        'too many sessions'                                         => '4.3.2',     #
        'Too much load'                                             => '4.3.2',     #
        'try again later'                                           => '4.3.2',     #
        'Try later'                                                 => '4.3.2',     #
        'retry timeout exceeded'                                    => '4.4.7',     #
        'queue too long'                                            => '4.4.7',     #

        '554 delivery error:'                                       => '5.1.1',     #SB: Yahoo/rogers.com generic delivery failure (see also OU-00)
        'account has been disabled'                                 => '5.1.1',     #
        'account is unavailable'                                    => '5.1.1',     #
        'Account not found'                                         => '5.1.1',     #
        'Address invalid'                                           => '5.1.1',     #
        'Address is unknown'                                        => '5.1.1',     #
        'Address unknown'                                           => '5.1.1',     #
        'Addressee unknown'                                         => '5.1.1',     #
        'ADDRESS_NOT_FOUND'                                         => '5.1.1',     #
        'bad address'                                               => '5.1.1',     #
        'Bad destination mailbox address'                           => '5.1.1',     #
        'destin. Sconosciuto'                                       => '5.1.1',     #.IT "user unknown"
        'Destinatario errato'                                       => '5.1.1',     #.IT "invalid"
        'Destinatario sconosciuto o mailbox disatttivata'           => '5.1.1',     #.IT "unknown /disabled"
        'does not exist'                                            => '5.1.1',     #
        'Email Address was not found'                               => '5.1.1',     #
        'Excessive userid unknowns'                                 => '5.1.1',     #
        'Indirizzo inesistente'                                     => '5.1.1',     #.IT "no user"
        'Invalid account'                                           => '5.1.1',     #
        'invalid address'                                           => '5.1.1',     #
        'Invalid or unknown virtual user'                           => '5.1.1',     #
        'Invalid mailbox'                                           => '5.1.1',     #
        'Invalid recipient'                                         => '5.1.1',     #
        'Mailbox not found'                                         => '5.1.1',     #
        'mailbox unavailable'                                       => '5.1.1',     #
        'nie istnieje'                                              => '5.1.1',     #.PL "does not exist"
        'Nie ma takiego konta'                                      => '5.1.1',     #.PL "no such account"
        'No mail box available for this user'                       => '5.1.1',     #
        'no mailbox here'                                           => '5.1.1',     #
        'No one with that email address here'                       => '5.1.1',     #
        'no such address'                                           => '5.1.1',     #
        'no such email address'                                     => '5.1.1',     #
        'No such mail drop defined'                                 => '5.1.1',     #
        'No such mailbox'                                           => '5.1.1',     #
        'No such person at this address'                            => '5.1.1',     #
        'no such recipient'                                         => '5.1.1',     #
        'No such user'                                              => '5.1.1',     #
        'not a known user'                                          => '5.1.1',     #
        'not a valid mailbox'                                       => '5.1.1',     #
        'not a valid user'                                          => '5.1.1',     #
        'not available'                                             => '5.1.1',     #
        'not exists'                                                => '5.1.1',     #
        'Recipient address rejected'                                => '5.1.1',     #
        'Recipient not allowed'                                     => '5.1.1',     #
        'Recipient not found'                                       => '5.1.1',     #
        'recipient rejected'                                        => '5.1.1',     #
        'Recipient unknown'                                         => '5.1.1',     #
        "server doesn't handle mail for that user"                  => '5.1.1',     #
        'This account is disabled'                                  => '5.1.1',     #
        'This address no longer accepts mail'                       => '5.1.1',     #
        'This email address is not known to this system'            => '5.1.1',     #
        'Unknown account'                                           => '5.1.1',     #
        'unknown address or alias'                                  => '5.1.1',     #
        'Unknown email address'                                     => '5.1.1',     #
        'Unknown local part'                                        => '5.1.1',     #
        'unknown or illegal alias'                                  => '5.1.1',     #
        'unknown or illegal user'                                   => '5.1.1',     #
        'Unknown recipient'                                         => '5.1.1',     #
        'unknown user'                                              => '5.1.1',     #
        'user disabled'                                             => '5.1.1',     #
        "User doesn't exist in this server"                         => '5.1.1',     #
        'user invalid'                                              => '5.1.1',     #
        'User is suspended'                                         => '5.1.1',     #
        'User is unknown'                                           => '5.1.1',     #
        'User not found'                                            => '5.1.1',     #
        'User not known'                                            => '5.1.1',     #
        'User unknown'                                              => '5.1.1',     #
        'valid RCPT command must precede DATA'                      => '5.1.1',     #
        'was not found in LDAP server'                              => '5.1.1',     #
        'We are sorry but the address is invalid'                   => '5.1.1',     #
        'Unable to find alias user'                                 => '5.1.1',     #

        "domain isn't in my list of allowed rcpthosts"              => '5.1.2',     #
        'Esta casilla ha expirado por falta de uso'                 => '5.1.2',     #BH ES:expired
        'host ?name is unknown'                                     => '5.1.2',     #
        'no relaying allowed'                                       => '5.1.2',     #
        'no such domain'                                            => '5.1.2',     #
        'not our customer'                                          => '5.1.2',     #
        'relay not permitted'                                       => '5.1.2',     #
        'Relay access denied'                                       => '5.1.2',     #
        'relaying denied'                                           => '5.1.2',     #
        'Relaying not allowed'                                      => '5.1.2',     #
        'This system is not configured to relay mail'               => '5.1.2',     #
        'Unable to relay'                                           => '5.1.2',     #
        'unrouteable mail domain'                                   => '5.1.2',     #BH
        'we do not relay'                                           => '5.1.2',     #

        'Old address no longer valid'                               => '5.1.6',     #
        'recipient no longer on server'                             => '5.1.6',     #

        'Sender address rejected'                                   => '5.1.8',     #

        'exceeded the rate limit'                                   => '5.2.0',     #
        'Local Policy Violation'                                    => '5.2.0',     #
        'Mailbox currently suspended'                               => '5.2.0',     #
        'mailbox unavailable'                                       => '5.2.0',     #
        'mail can not be delivered'                                 => '5.2.0',     #
        'Delivery failed'                                           => '5.2.0',     #
        'mail couldn\'t be delivered'                               => '5.2.0',     #
        'The account or domain may not exist'                       => '5.2.0',     #I guess.... seems like 5.1.1, 5.1.2, or 5.4.4 would fit too, but 5.2.0 seemed most generic

        'Account disabled'                                          => '5.2.1',     #
        'account has been disabled'                                 => '5.2.1',     #
        'Account Inactive'                                          => '5.2.1',     #
        'Adressat unbekannt oder Mailbox deaktiviert'               => '5.2.1',     #
        'Destinataire inconnu ou boite aux lettres desactivee'      => '5.2.1',     #.FR disabled
        'mail is not currently being accepted for this mailbox'     => '5.2.1',     #
        'El usuario esta en estado: inactivo'                       => '5.2.1',     #.IT inactive
        'email account that you tried to reach is disabled'         => '5.2.1',     #
        'inactive user'                                             => '5.2.1',     #
        'Mailbox disabled for this recipient'                       => '5.2.1',     #
        'mailbox has been blocked due to inactivity'                => '5.2.1',     #
        'mailbox is currently unavailable'                          => '5.2.1',     #
        'Mailbox is disabled'                                       => '5.2.1',     #
        'Mailbox is inactive'                                       => '5.2.1',     #
        'Mailbox Locked or Suspended'                               => '5.2.1',     #
        'mailbox temporarily disabled'                              => '5.2.1',     #
        'Podane konto jest zablokowane administracyjnie lub nieaktywne'=> '5.2.1',  #.PL locked or inactive
        "Questo indirizzo e' bloccato per inutilizzo"               => '5.2.1',     #.IT blocked/expired
        'Recipient mailbox was disabled'                            => '5.2.1',     #
        'Domain name not found'                                     => '5.2.1',

        'couldn\'t find any host named'                             => '5.4.4',     #
        'couldn\'t find any host by that name'                      => '5.4.4',     #
        'PERM_FAILURE: DNS Error'                                   => '5.4.4',     #SB: Routing failure
        'Temporary lookup failure'                                  => '5.4.4',     #
        'unrouteable address'                                       => '5.4.4',     #
        "can't connect to"                                          => '5.4.4',     #

        'Too many hops'                                             => '5.4.6',     #

        'Requested action aborted'                                  => '5.5.0',     #

        'rejecting password protected file attachment'              => '5.6.2',     #RFC "Conversion required and prohibited"

        '550 OU-00'                                                 => '5.7.1',     #SB hotmail returns a OU-001 if you're on their blocklist
        '550 SC-00'                                                 => '5.7.1',     #SB hotmail returns a SC-00x if you're on their blocklist
        '550 DY-00'                                                 => '5.7.1',     #SB hotmail returns a DY-00x if you're a dynamic IP
        '554 denied'                                                => '5.7.1',     #
        'You have been blocked by the recipient'                    => '5.7.1',     #
        'requires that you verify'                                  => '5.7.1',     #
        'Access denied'                                             => '5.7.1',     #
        'Administrative prohibition - unable to validate recipient' => '5.7.1',     #
        'Blacklisted'                                               => '5.7.1',     #
        'blocke?d? for spam'                                        => '5.7.1',     #
        'conection refused'                                         => '5.7.1',     #
        'Connection refused due to abuse'                           => '5.7.1',     #
        'dial-up or dynamic-ip denied'                              => '5.7.1',     #
        'Domain has received too many bounces'                      => '5.7.1',     #
        'failed several antispam checks'                            => '5.7.1',     #
        'found in a DNS blacklist'                                  => '5.7.1',     #
        'IPs blocked'                                               => '5.7.1',     #
        'is blocked by'                                             => '5.7.1',     #
        'Mail Refused'                                              => '5.7.1',     #
        'Message does not pass DomainKeys'                          => '5.7.1',     #
        'Message looks like spam'                                   => '5.7.1',     #
        'Message refused by'                                        => '5.7.1',     #
        'not allowed access from your location'                     => '5.7.1',     #
        'permanently deferred'                                      => '5.7.1',     #
        'Rejected by policy'                                        => '5.7.1',     #
        'rejected by Windows Live Hotmail for policy reasons'       => '5.7.1',     #SB Yes, should be 5.7.1; Kanon added Again, why isnt this 5.7.1 instead?
        'Rejected for policy reasons'                               => '5.7.1',     #
        'Rejecting banned content'                                  => '5.7.1',     #
        'Sorry, looks like spam'                                    => '5.7.1',     #
        'spam message discarded'                                    => '5.7.1',     #
        'Too many spams from your IP'                               => '5.7.1',     #
        'TRANSACTION FAILED'                                        => '5.7.1',     #
        'Transaction rejected'                                      => '5.7.1',     #
        'Wiadomosc zostala odrzucona przez system antyspamowy'      => '5.7.1',     #.PL rejected as spam
        'Your message was declared Spam'                            => '5.7.1'      #
    );

    public $autorespondlist = array(
        '^\[?auto.{0,20}reply\]?',
        '^auto-?response',
        '^auto response',
        '^Thank you for your email\.',
        '^Vacation.{0,20}(reply|respon)',
        '^out.?of (the )?office',
        '^(I am|I\'m).{0,20}\s(away|on vacation|on leave|out of office|out of the office)',
        "\350\207\252\345\212\250\345\233\236\345\244\215"   #sino.com,  163.com  UTF8 encoded
    );

    public static $status_code_classes = array(
        '2' => array(
            'title' => "Success",
            'descr' => "Success specifies that the DSN is reporting a positive delivery action.  Detail sub-codes may provide notification of transformations required for delivery."
        ),
        '4' => array(
            'title' => "Persistent Transient Failure",
            'descr' => "A persistent transient failure is one in which the message as sent is valid, but some temporary event prevents the successful sending of the message.  Sending in the future may be successful."
        ),
        '5' => array(
            'title' => "Permanent Failure",
            'descr' => "A permanent failure is one which is not likely to be resolved by resending the message in the current form.  Some change to the message or the destination must be made for successful delivery."
        )
    );

    public static $status_code_subclasses = array(
        array('0.0' => array(
            'title' =>  "Other undefined Status",
            'descr' =>  "Other undefined status is the only undefined error code. It should be used for all errors for which only the class of the error is known."
            )
        ),
        array('1.0' => array(
            'title' =>  "Other address status",
            'descr' =>  "Something about the address specified in the message caused this DSN."
            )
        ),
        array('1.1' => array(
            'title' =>  "Bad destination mailbox address",
            'descr' =>  "The mailbox specified in the address does not exist.  For Internet mail names, this means the address portion to the left of the @ sign is invalid.  This code is only useful for permanent failures."
            )
        ),
        array('1.2' => array(
            'title' =>  "Bad destination system address",
            'descr' =>  "The destination system specified in the address does not exist or is incapable of accepting mail.  For Internet mail names, this means the address portion to the right of the @ is invalid for mail.  This codes is only useful for permanent failures."
            )
        ),
        array('1.3' => array(
            'title' => "Bad destination mailbox address syntax",
            'descr' =>  "The destination address was syntactically invalid.  This can apply to any field in the address.  This code is only useful for permanent failures."
            )
        ),
        array('1.4' => array(
            'title' => "Destination mailbox address ambiguous",
            'descr' =>  "The mailbox address as specified matches one or more recipients on the destination system.  This may result if a heuristic address mapping algorithm is used to map the specified address to a local mailbox name."
            )
        ),
        array('1.5' => array(
            'title' => "Destination address valid",
            'descr' =>  "This mailbox address as specified was valid.  This status code should be used for positive delivery reports."
            )
        ),
        array('1.6' => array(
            'title' => "Destination mailbox has moved, No forwarding address",
            'descr' =>  "The mailbox address provided was at one time valid, but mail is no longer being accepted for that address.  This code is only useful for permanent failures."
            )
        ),
        array('1.7' => array(
            'title' => "Bad sender's mailbox address syntax",
            'descr' =>  "The sender's address was syntactically invalid.  This can apply to any field in the address."
            )
        ),
        array('1.8' => array(
            'title' => "Bad sender's system address",
            'descr' =>  "The sender's system specified in the address does not exist or is incapable of accepting return mail.  For domain names, this means the address portion to the right of the @ is invalid for mail. "
            )
        ),
        array('2.0' => array(
            'title' => "Other or undefined mailbox status",
            'descr' =>  "The mailbox exists, but something about the destination mailbox has caused the sending of this DSN."
            )
        ),
        array('2.1' => array(
            'title' => "Mailbox disabled, not accepting messages",
            'descr' =>  "The mailbox exists, but is not accepting messages.  This may be a permanent error if the mailbox will never be re-enabled or a transient error if the mailbox is only temporarily disabled."
            )
        ),
        array('2.2' => array(
            'title' => "Mailbox full",
            'descr' =>  "The mailbox is full because the user has exceeded a per-mailbox administrative quota or physical capacity.  The general semantics implies that the recipient can delete messages to make more space available.  This code should be used as a persistent transient failure."
            )
        ),
        array('2.3' => array(
            'title' => "Message length exceeds administrative limit",
            'descr' =>  "A per-mailbox administrative message length limit has been exceeded. This status code should be used when the per-mailbox message length limit is less than the general system limit.  This code should be used as a permanent failure."
            )
        ),
        array('2.4' => array(
            'title' => "Mailing list expansion problem",
            'descr' =>  "The mailbox is a mailing list address and the mailing list was unable to be expanded. This code may represent a permanent failure or a persistent transient failure."
            )
        ),
        array('3.0' => array(
            'title' => "Other or undefined mail system status",
            'descr' =>  "The destination system exists and normally accepts mail, but something about the system has caused the generation of this DSN."
            )
        ),
        array('3.1' => array(
            'title' => "Mail system full",
            'descr' =>  "Mail system storage has been exceeded.  The general semantics imply that the individual recipient may not be able to delete material to make room for additional messages.  This is useful only as a persistent transient error."
            )
        ),
        array('3.2' => array(
            'title' => "System not accepting network messages",
            'descr' =>  "The host on which the mailbox is resident is not accepting messages.  Examples of such conditions include an immanent shutdown, excessive load, or system maintenance.  This is useful for both permanent and permanent transient errors. "
            )
        ),
        array('3.3' => array(
            'title' => "System not capable of selected features",
            'descr' =>  "Selected features specified for the message are not supported by the destination system.  This can occur in gateways when features from one domain cannot be mapped onto the supported feature in another."
            )
        ),
        array('3.4' => array(
            'title' => "Message too big for system",
            'descr' =>  "The message is larger than per-message size limit.  This limit may either be for physical or administrative reasons. This is useful only as a permanent error."
            )
        ),
        array('3.5' => array(
            'title' => "System incorrectly configured",
            'descr' =>  "The system is not configured in a manner which will permit it to accept this message."
            )
        ),
        array('4.0' => array(
            'title' => "Other or undefined network or routing status",
            'descr' =>  "Something went wrong with the networking, but it is not clear what the problem is, or the problem cannot be well expressed with any of the other provided detail codes."
            )
        ),
        array('4.1' => array(
            'title' => "No answer from host",
            'descr' =>  "The outbound connection attempt was not answered, either because the remote system was busy, or otherwise unable to take a call.  This is useful only as a persistent transient error."
            )
        ),
        array('4.2' => array(
            'title' => "Bad connection",
            'descr' =>  "The outbound connection was established, but was otherwise unable to complete the message transaction, either because of time-out, or inadequate connection quality. This is useful only as a persistent transient error."
            )
        ),
        array('4.3' => array(
            'title' => "Directory server failure",
            'descr' =>  "The network system was unable to forward the message, because a directory server was unavailable.  This is useful only as a persistent transient error. The inability to connect to an Internet DNS server is one example of the directory server failure error. "
            )
        ),
        array('4.4' => array(
            'title' => "Unable to route",
            'descr' =>  "The mail system was unable to determine the next hop for the message because the necessary routing information was unavailable from the directory server. This is useful for both permanent and persistent transient errors.  A DNS lookup returning only an SOA (Start of Administration) record for a domain name is one example of the unable to route error."
            )
        ),
        array('4.5' => array(
            'title' => "Mail system congestion",
            'descr' =>  "The mail system was unable to deliver the message because the mail system was congested. This is useful only as a persistent transient error."
            )
        ),
        array('4.6' => array(
            'title' => "Routing loop detected",
            'descr' =>  "A routing loop caused the message to be forwarded too many times, either because of incorrect routing tables or a user forwarding loop. This is useful only as a persistent transient error."
            )
        ),
        array('4.7' => array(
            'title' => "Delivery time expired",
            'descr' =>  "The message was considered too old by the rejecting system, either because it remained on that host too long or because the time-to-live value specified by the sender of the message was exceeded. If possible, the code for the actual problem found when delivery was attempted should be returned rather than this code.  This is useful only as a persistent transient error."
            )
        ),
        array('5.0' => array(
            'title' => "Other or undefined protocol status",
            'descr' =>  "Something was wrong with the protocol necessary to deliver the message to the next hop and the problem cannot be well expressed with any of the other provided detail codes."
            )
        ),
        array('5.1' => array(
            'title' => "Invalid command",
            'descr' =>  "A mail transaction protocol command was issued which was either out of sequence or unsupported.  This is useful only as a permanent error."
            )
        ),
        array('5.2' => array(
            'title' => "Syntax error",
            'descr' =>  "A mail transaction protocol command was issued which could not be interpreted, either because the syntax was wrong or the command is unrecognized. This is useful only as a permanent error."
            )
        ),
        array('5.3' => array(
            'title' => "Too many recipients",
            'descr' =>  "More recipients were specified for the message than could have been delivered by the protocol.  This error should normally result in the segmentation of the message into two, the remainder of the recipients to be delivered on a subsequent delivery attempt.  It is included in this list in the event that such segmentation is not possible."
            )
        ),
        array('5.4' => array(
            'title' => "Invalid command arguments",
            'descr' =>  "A valid mail transaction protocol command was issued with invalid arguments, either because the arguments were out of range or represented unrecognized features. This is useful only as a permanent error. "
            )
        ),
        array('5.5' => array(
            'title' => "Wrong protocol version",
            'descr' =>  "A protocol version mis-match existed which could not be automatically resolved by the communicating parties."
            )
        ),
        array('6.0' => array(
            'title' => "Other or undefined media error",
            'descr' =>  "Something about the content of a message caused it to be considered undeliverable and the problem cannot be well expressed with any of the other provided detail codes. "
            )
        ),
        array('6.1' => array(
            'title' => "Media not supported",
            'descr' =>  "The media of the message is not supported by either the delivery protocol or the next system in the forwarding path. This is useful only as a permanent error."
            )
        ),
        array('6.2' => array(
            'title' => "Conversion required and prohibited",
            'descr' =>  "The content of the message must be converted before it can be delivered and such conversion is not permitted.  Such prohibitions may be the expression of the sender in the message itself or the policy of the sending host."
            )
        ),
        array('6.3' => array(
            'title' => "Conversion required but not supported",
            'descr' =>  "The message content must be converted to be forwarded but such conversion is not possible or is not practical by a host in the forwarding path.  This condition may result when an ESMTP gateway supports 8bit transport but is not able to downgrade the message to 7 bit as required for the next hop."
            )
        ),
        array('6.4' => array(
            'title' => "Conversion with loss performed",
            'descr' =>  "This is a warning sent to the sender when message delivery was successfully but when the delivery required a conversion in which some data was lost.  This may also be a permanant error if the sender has indicated that conversion with loss is prohibited for the message."
            )
        ),
        array('6.5' => array(
            'title' => "Conversion Failed",
            'descr' =>  "A conversion was required but was unsuccessful.  This may be useful as a permanent or persistent temporary notification."
            )
        ),
        array('7.0' => array(
            'title' => "Other or undefined security status",
            'descr' =>  "Something related to security caused the message to be returned, and the problem cannot be well expressed with any of the other provided detail codes.  This status code may also be used when the condition cannot be further described because of security policies in force."
            )
        ),
        array('7.1' => array(
            'title' => "Delivery not authorized, message refused",
            'descr' =>  "The sender is not authorized to send to the destination. This can be the result of per-host or per-recipient filtering.  This memo does not discuss the merits of any such filtering, but provides a mechanism to report such. This is useful only as a permanent error."
            )
        ),
        array('7.2' => array(
            'title' => "Mailing list expansion prohibited",
            'descr' =>  "The sender is not authorized to send a message to the intended mailing list. This is useful only as a permanent error."
            )
        ),
        array('7.3' => array(
            'title' => "Security conversion required but not possible",
            'descr' =>  "A conversion from one secure messaging protocol to another was required for delivery and such conversion was not possible. This is useful only as a permanent error. "
            )
        ),
        array('7.4' => array(
            'title' => "Security features not supported",
            'descr' =>  "A message contained security features such as secure authentication which could not be supported on the delivery protocol. This is useful only as a permanent error."
            )
        ),
        array('7.5' => array(
            'title' => "Cryptographic failure",
            'descr' =>  "A transport system otherwise authorized to validate or decrypt a message in transport was unable to do so because necessary information such as key was not available or such information was invalid."
            )
        ),
        array('7.6' => array(
            'title' => "Cryptographic algorithm not supported",
            'descr' =>  "A transport system otherwise authorized to validate or decrypt a message was unable to do so because the necessary algorithm was not supported. "
            )
        ),
        array('7.7' => array(
            'title' => "Message integrity failure",
            'descr' =>  "A transport system otherwise authorized to validate a message was unable to do so because the message was corrupted or altered.  This may be useful as a permanent, transient persistent, or successful delivery code."
            )
        ),
    );



    /**** INSTANTIATION *******************************************************/
    public function __construct(){
        $this->output[0]['action']  = "";
        $this->output[0]['status']  = "";
        $this->output[0]['recipient'] = "";
    }


    /**** METHODS *************************************************************/
    // this is the most commonly used public method
    // quick and dirty
    // useage: $multiArray = $this->get_the_facts($strEmail);
    public function parse_email($eml){
        return $this->get_the_facts($eml);
    }
    public function get_the_facts($eml){
        // fluff up the email
        $bounce = $this->init_bouncehandler($eml);
        list($head, $body) = preg_split("/\r\n\r\n/", $bounce, 2);
        $this->head_hash = $this->parse_head($head);

        // parse the email into data structures
        $boundary = @$this->head_hash['Content-type']['boundary'];
        $mime_sections = $this->parse_body_into_mime_sections($body, $boundary);
        $this->body_hash = preg_split("/\r\n/", $body);
        $this->first_body_hash = $this->parse_head(@$mime_sections['first_body_part']);

        $this->looks_like_a_bounce = $this->is_a_bounce();
        $this->looks_like_an_FBL = $this->is_an_ARF();
        $this->looks_like_an_autoresponse = $this->is_an_autoresponse();


        /* If you are trying to save processing power, and don't care much
         * about accuracy then uncomment this statement in order to skip the
         * heroic text parsing below.
         */
        //if(!$this->looks_like_a_bounce && !$this->looks_like_an_FBL && !$this->looks_like_an_autoresponse){
        //    return "unknown";
        //}


        /*** now we try all our weird text parsing methods (E-mail is weird!) ******************************/

        // is it a Feedback Loop, in Abuse Feedback Reporting Format (ARF)?
        // http://en.wikipedia.org/wiki/Abuse_Reporting_Format#Abuse_Feedback_Reporting_Format_.28ARF.29
        if($this->looks_like_an_FBL){
            $this->output[0]['action'] = 'failed';
            $this->output[0]['status'] = "5.7.1";
            $this->subject = trim(str_ireplace("Fw:", "", $this->head_hash['Subject']));
            if($this->is_hotmail_fbl === true){
                // fill in the fbl_hash with sensible values
                $this->fbl_hash['Content-disposition'] = 'inline';
                $this->fbl_hash['Content-type'] = 'message/feedback-report';
                $this->fbl_hash['Feedback-type'] = 'abuse';
                $this->fbl_hash['User-agent'] = 'Hotmail FBL';
                if (isset($this->first_body_hash['Date'])) {
                    $this->fbl_hash['Received-date'] = $this->first_body_hash['Date'];
                }
                if (!empty($this->recipient)){
                    $this->fbl_hash['Original-rcpt-to'] = $this->recipient;
                }
                if(isset($this->first_body_hash['X-sid-pra'])){
                    $this->fbl_hash['Original-mail-from'] = $this->first_body_hash['X-sid-pra'];
                }
            }
            else{
                $this->fbl_hash = $this->standard_parser($mime_sections['machine_parsable_body_part']);
                $returnedhash = $this->standard_parser($mime_sections['returned_message_body_part']);
                if (empty($this->fbl_hash['Original-mail-from']) && !empty($returnedhash['From'])) {
                    $this->fbl_hash['Original-mail-from'] = $returnedhash['From'];
                }
                if (empty($this->fbl_hash['Original-rcpt-to']) && !empty($this->fbl_hash['Removal-recipient']) ) {
                    $this->fbl_hash['Original-rcpt-to'] = $this->fbl_hash['Removal-recipient'];
                }
                elseif (!empty($returnedhash['To'])) {
                    $this->fbl_hash['Original-rcpt-to'] = $returnedhash['To'];
                }
            }
            // warning, some servers will remove the name of the original intended recipient from the FBL report,
            // replacing it with redacted@rcpt-hostname.com, making it utterly useless, of course (unless you used a web-beacon).
            // here we try our best to give you the actual intended recipient, if possible.
            if (preg_match('/Undisclosed|redacted/i', $this->fbl_hash['Original-rcpt-to']) && isset($this->fbl_hash['Removal-recipient']) ) {
                $this->fbl_hash['Original-rcpt-to'] = @$this->fbl_hash['Removal-recipient'];
            }
            if (empty($this->fbl_hash['Received-date']) && !empty($this->fbl_hash[@'Arrival-date']) ) {
                $this->fbl_hash['Received-date'] = @$this->fbl_hash['Arrival-date'];
            }
            $this->fbl_hash['Original-mail-from'] = $this->strip_angle_brackets(@$this->fbl_hash['Original-mail-from']);
            $this->fbl_hash['Original-rcpt-to']   = $this->strip_angle_brackets(@$this->fbl_hash['Original-rcpt-to']);
            $this->output[0]['recipient'] = $this->fbl_hash['Original-rcpt-to'];
        }

        else if (preg_match("/auto.{0,20}reply|vacation|(out|away|on holiday).*office/i", $this->head_hash['Subject'])){
            // looks like a vacation autoreply, ignoring
            $this->output[0]['action'] = 'autoreply';
        }

        // is this an autoresponse ?
        else if ($this->looks_like_an_autoresponse) {
            $this->output[0]['action'] = 'transient';
            $this->output[0]['status'] = '4.3.2';
            // grab the first recipient and break
            $this->output[0]['recipient'] = isset($this->head_hash['Return-path']) ? $this->strip_angle_brackets($this->head_hash['Return-path']) : '';
            if(empty($this->output[0]['recipient'])){
                $arrFailed = $this->find_email_addresses($body);
                for($j=0; $j<count($arrFailed); $j++){
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    break;
                }
            }
        }

        else if ($this->is_RFC1892_multipart_report() === TRUE){
            $rpt_hash = $this->parse_machine_parsable_body_part($mime_sections['machine_parsable_body_part']);
            for($i=0; $i<count($rpt_hash['per_recipient']); $i++){
                $this->output[$i]['recipient'] = $this->find_recipient($rpt_hash['per_recipient'][$i]);
                $mycode = @$this->format_status_code($rpt_hash['per_recipient'][$i]['Status']);
                $this->output[$i]['status'] = @$mycode['code'];
                $this->output[$i]['action'] = @$rpt_hash['per_recipient'][$i]['Action'];
            }
        }

        else if(isset($this->head_hash['X-failed-recipients'])) {
            //  Busted Exim MTA
            //  Up to 50 email addresses can be listed on each header.
            //  There can be multiple X-Failed-Recipients: headers. - (not supported)
            $arrFailed = preg_split("/\,/", $this->head_hash['X-failed-recipients']);
            for($j=0; $j<count($arrFailed); $j++){
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['status'] = $this->get_status_code_from_text($this->output[$j]['recipient'],0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['status']);
            }
        }

        else if(!empty($boundary) && $this->looks_like_a_bounce){
            // oh god it could be anything, but at least it has mime parts, so let's try anyway
            $arrFailed = $this->find_email_addresses($mime_sections['first_body_part']);
            for($j=0; $j<count($arrFailed); $j++){
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['status'] = $this->get_status_code_from_text($this->output[$j]['recipient'],0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['status']);
            }
        }

        else if($this->looks_like_a_bounce){
            // last ditch attempt
            // could possibly produce erroneous output, or be very resource consuming,
            // so be careful.  You should comment out this section if you are very concerned
            // about 100% accuracy or if you want very fast performance.
            // Leave it turned on if you know that all messages to be analyzed are bounces.
            $arrFailed = $this->find_email_addresses($body);
            for($j=0; $j<count($arrFailed); $j++){
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['status'] = $this->get_status_code_from_text($this->output[$j]['recipient'],0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['status']);
            }
        }
        // else if()..... add a parser for your busted-ass MTA here

        // remove empty array indices
        $tmp = array();
        foreach($this->output as $arr){
            if(empty($arr['recipient']) && empty($arr['status']) && empty($arr['action']) ){
                continue;
            }
            $tmp[] = $arr;
        }
        $this->output = $tmp;

        // accessors
        /*if it is an FBL, you could use the class variables to access the
        data (Unlike Multipart-reports, FBL's report only one bounce)
        */
        $this->type = $this->find_type();
        $this->action = $this->output[0]['action'];
        $this->status = $this->output[0]['status'];
        $this->subject = ($this->subject) ? $this->subject : $this->head_hash['Subject'];
        $this->recipient = $this->output[0]['recipient'];
        $this->feedback_type = @($this->fbl_hash['Feedback-type']) ? $this->fbl_hash['Feedback-type'] : "";

        // sniff out any web beacons
        if($this->web_beacon_preg_1)
            $this->web_beacon_1 = $this->find_web_beacon($body, $this->web_beacon_preg_1);
        if($this->web_beacon_preg_2)
            $this->web_beacon_2 = $this->find_web_beacon($body, $this->web_beacon_preg_2);
        if($this->x_header_search_1)
            $this->x_header_beacon_1 = $this->find_x_header  ($this->x_header_search_1);
        if($this->x_header_search_2)
            $this->x_header_beacon_2 = $this->find_x_header  ($this->x_header_search_2);

        return $this->output;
    }



    function init_bouncehandler($blob, $format='string'){
        $this->head_hash = array();
        $this->fbl_hash = array();
        $this->body_hash = array();
        $this->looks_like_a_bounce = false;
        $this->looks_like_an_FBL = false;
        $this->is_hotmail_fbl = false;
        $this->type = "";
        $this->feedback_type = "";
        $this->action = "";
        $this->status = "";
        $this->subject = "";
        $this->recipient = "";
        $this->output = array();

        // TODO: accept several formats (XML, string, array)
        // currently accepts only string
        //if($format=='xml_array'){
        //    $strEmail = "";
        //    $out = "";
        //    for($i=0; $i<$blob; $i++){
        //        $out = preg_replace("/<HEADER>/i", "", $blob[$i]);
        //        $out = preg_replace("/</HEADER>/i", "", $out);
        //        $out = preg_replace("/<MESSAGE>/i", "", $out);
        //        $out = preg_replace("/</MESSAGE>/i", "", $out);
        //        $out = rtrim($out) . "\r\n";
        //        $strEmail .= $out;
        //    }
        //}
        //else if($format=='string'){

            $strEmail = str_replace("\r\n", "\n", $blob);    // line returns 1
            $strEmail = str_replace("\n", "\r\n", $strEmail);// line returns 2
            $strEmail = str_replace("=\r\n", "", $strEmail); // remove MIME line breaks
            $strEmail = str_replace("=3D", "=", $strEmail);  // equals sign =
            $strEmail = str_replace("=09", "  ", $strEmail); // tabs

        //}
        //else if($format=='array'){
        //    $strEmail = "";
        //    for($i=0; $i<$blob; $i++){
        //        $strEmail .= rtrim($blob[$i]) . "\r\n";
        //    }
        //}

        return $strEmail;
    }

    // general purpose recursive heuristic function
    // to try to extract useful info from the bounces produced by busted MTAs
    function get_status_code_from_text($recipient, $index){
        for($i=$index; $i<count($this->body_hash); $i++){
            $line = trim($this->body_hash[$i]);

            /******** recurse into the email if you find the recipient ********/
            if(stristr($line, $recipient)!==FALSE){
                // the status code MIGHT be in the next few lines after the recipient line,
                // depending on the message from the foreign host... What a laugh riot!
                $status_code = $this->get_status_code_from_text($recipient, $i+1);
                if($status_code){
                    return $status_code;
                }

            }

            /******** exit conditions ********/
            // if it's the end of the human readable part in this stupid bounce
            if(stristr($line, '------ This is a copy of the message')!==FALSE){
                return '';
            }
            //if we see an email address other than our current recipient's,
            if(count($this->find_email_addresses($line))>=1
               && stristr($line, $recipient)===FALSE
               && strstr($line, 'FROM:<')===FALSE){ // Kanon added this line because Hotmail puts the e-mail address too soon and there actually is error message stuff after it.
                return '';
            }
            /******** pattern matching ********/
            foreach ($this->bouncelist as $bouncetext => $bouncecode) {
              if (preg_match("/$bouncetext/i", $line, $matches)) {
                return (isset($matches[1])) ? $matches[1] : $bouncecode;
              }
            }

            // rfc1893 return code
            if(preg_match('/\W([245]\.[01234567]\.[012345678])\W/', $line, $matches)){
                if(stripos($line, 'Message-ID')!==FALSE){
                    break;
                }
                $mycode = str_replace('.', '', $matches[1]);
                $mycode = $this->format_status_code($mycode);
                return implode('.', $mycode['code']);
            }

            // search for RFC821 return code
            // thanks to mark.tolman@gmail.com
            // Maybe at some point it should have it's own place within the main parsing scheme (at line 88)
            if(preg_match('/\]?: ([45][01257][012345]) /', $line, $matches)
               || preg_match('/^([45][01257][012345]) (?:.*?)(?:denied|inactive|deactivated|rejected|disabled|unknown|no such|not (?:our|activated|a valid))+/i', $line, $matches))
            {
                $mycode = $matches[1];
                // map common codes to new rfc values
                if($mycode == '450' || $mycode == '550' || $mycode == '551' || $mycode == '554'){
                    $mycode = '511';
                } else if($mycode == '452' || $mycode == '552'){
                    $mycode = '422';
                } else if ($mycode == '421'){
                    $mycode = '432';
                }
                $mycode = $this->format_status_code($mycode);
                return implode('.', $mycode['code']);
            }

        }
        return '';
    }

    function is_RFC1892_multipart_report(){
        return @$this->head_hash['Content-type']['type']=='multipart/report'
           &&  @$this->head_hash['Content-type']['report-type']=='delivery-status'
           &&  @$this->head_hash['Content-type'][boundary]!=='';
    }

    function parse_head($headers){
        if(!is_array($headers)) $headers = explode("\r\n", $headers);
        $hash = $this->standard_parser($headers);
        if(!empty($hash['Content-type'])){//preg_match('/Multipart\/Report/i', $hash['Content-type'])){
            $multipart_report = explode (';', $hash['Content-type']);
            $hash['Content-type']='';
            $hash['Content-type']['type'] = strtolower($multipart_report[0]);
            foreach($multipart_report as $mr){
                if(preg_match('/([^=.]*?)=(.*)/i', $mr, $matches)){
                // didn't work when the content-type boundary ID contained an equal sign,
                // that exists in bounces from many Exchange servers
                //if(preg_match('/([a-z]*)=(.*)?/i', $mr, $matches)){
                    $hash['Content-type'][strtolower(trim($matches[1]))]= str_replace('"','',$matches[2]);
                }
            }
        }
        return $hash;
    }

    function parse_body_into_mime_sections($body, $boundary){
        if(!$boundary) return array();
        if(is_array($body)) $body = implode("\r\n", $body);
        $body = explode($boundary, $body);
        $mime_sections['first_body_part']            = @$body[1];
        $mime_sections['machine_parsable_body_part'] = @$body[2];
        $mime_sections['returned_message_body_part'] = @$body[3];
        return $mime_sections;
    }


    function standard_parser($content){ // associative array orstr
        // receives email head as array of lines
        // simple parse (Entity: value\n)
        $hash = array('Received'=>'');
        if(!is_array($content)) $content = explode("\r\n", $content);
        foreach($content as $line){
            if(preg_match('/^([^\s.]*):\s*(.*)\s*/', $line, $array)){
                $entity = ucfirst(strtolower($array[1]));
                if(empty($hash[$entity])){
                    $hash[$entity] = trim($array[2]);
                }
                else if($hash['Received']){
                    // grab extra Received headers :(
                    // pile it on with pipe delimiters,
                    // oh well, SMTP is broken in this way
                    if ($entity and $array[2] and $array[2] != $hash[$entity]){
                        $hash[$entity] .= "|" . trim($array[2]);
                    }
                }
            }
            elseif (preg_match('/^\s+(.+)\s*/', $line) && !empty($entity)) {
                $hash[$entity] .= ' '. $line;
            }
        }
        // special formatting
        $hash['Received']= @explode('|', $hash['Received']);
        $hash['Subject'] = iconv_mime_decode($hash['Subject'], 0, "ISO-8859-1");

        return $hash;
    }

    function parse_machine_parsable_body_part($str){
        //Per-Message DSN fields
        $hash = $this->parse_dsn_fields($str);
        $hash['mime_header'] = $this->standard_parser($hash['mime_header']);
        $hash['per_message'] = $this->standard_parser($hash['per_message']);
        if(!empty($hash['per_message']['X-postfix-sender'])){
            $arr = explode (';', $hash['per_message']['X-postfix-sender']);
            $hash['per_message']['X-postfix-sender']='';
            $hash['per_message']['X-postfix-sender']['type'] = @trim($arr[0]);
            $hash['per_message']['X-postfix-sender']['addr'] = @trim($arr[1]);
        }
        if(!empty($hash['per_message']['Reporting-mta'])){
            $arr = explode (';', $hash['per_message']['Reporting-mta']);
            $hash['per_message']['Reporting-mta']='';
            $hash['per_message']['Reporting-mta']['type'] = @trim($arr[0]);
            $hash['per_message']['Reporting-mta']['addr'] = @trim($arr[1]);
        }
        //Per-Recipient DSN fields
        for($i=0; $i<count($hash['per_recipient']); $i++){
            $temp = $this->standard_parser(explode("\r\n", $hash['per_recipient'][$i]));
            $arr = @explode (';', $temp['Final-recipient']);
            $temp['Final-recipient'] = $this->format_final_recipient_array($arr);
            //$temp['Final-recipient']['type'] = trim($arr[0]);
            //$temp['Final-recipient']['addr'] = trim($arr[1]);
            $arr = @explode (';', $temp['Original-recipient']);
            $temp['Original-recipient']='';
            $temp['Original-recipient']['type'] = @trim($arr[0]);
            $temp['Original-recipient']['addr'] = @trim($arr[1]);
            $arr = @explode (';', $temp['Diagnostic-code']);
            $temp['Diagnostic-code']='';
            $temp['Diagnostic-code']['type'] = @trim($arr[0]);
            $temp['Diagnostic-code']['text'] = @trim($arr[1]);
            // now this is wierd: plenty of times you see the status code is a permanent failure,
            // but the diagnostic code is a temporary failure.  So we will assert the most general
            // temporary failure in this case.
            $ddc=''; $judgement='';
            $ddc = $this->decode_diagnostic_code($temp['Diagnostic-code']['text']);
            $judgement = $this->get_action_from_status_code($ddc);
            if($judgement == 'transient'){
                if(stristr($temp['Action'],'failed')!==FALSE){
                    $temp['Action']='transient';
                    $temp['Status']='4.3.0';
                }
            }
            $hash['per_recipient'][$i]='';
            $hash['per_recipient'][$i]=$temp;
        }
        return $hash;
    }

    function get_head_from_returned_message_body_part($mime_sections){
        $temp = explode("\r\n\r\n", $mime_sections[returned_message_body_part]);
        $head = $this->standard_parser($temp[1]);
        $head['From'] = $this->extract_address($head['From']);
        $head['To'] = $this->extract_address($head['To']);
        return $head;
    }

    function extract_address($str){
        $from_stuff = preg_split('/[ \"\'\<\>:\(\)\[\]]/', $str);
        foreach ($from_stuff as $things){
            if (strpos($things, '@')!==FALSE){$from = $things;}
        }
        return $from;
    }

    function find_recipient($per_rcpt){
        $recipient = '';
        if($per_rcpt['Original-recipient']['addr'] !== ''){
            $recipient = $per_rcpt['Original-recipient']['addr'];
        }
        else if($per_rcpt['Final-recipient']['addr'] !== ''){
            $recipient = $per_rcpt['Final-recipient']['addr'];
        }
        $recipient = $this->strip_angle_brackets($recipient);
        return $recipient;
    }

    function find_type(){
        if($this->looks_like_a_bounce)
            return "bounce";
        else if($this->looks_like_an_FBL)
            return "fbl";
        else
            return false;
    }

    function parse_dsn_fields($dsn_fields){
        if(!is_array($dsn_fields)) $dsn_fields = explode("\r\n\r\n", $dsn_fields);
        $j = 0;
        reset($dsn_fields);
        for($i=0; $i<count($dsn_fields); $i++){
            $dsn_fields[$i] = trim($dsn_fields[$i]);
            if($i==0)
                $hash['mime_header'] = $dsn_fields[0];
            elseif($i==1 && !preg_match('/(Final|Original)-Recipient/',$dsn_fields[1])) {
                // some mta's don't output the per_message part, which means
                // the second element in the array should really be
                // per_recipient - test with Final-Recipient - which should always
                // indicate that the part is a per_recipient part
                $hash['per_message'] = $dsn_fields[1];
            }
            else {
                if($dsn_fields[$i] == '--') continue;
                $hash['per_recipient'][$j] = $dsn_fields[$i];
                $j++;
            }
        }
        return $hash;
    }

    function format_status_code($code){
        $ret = "";
        if(preg_match('/([245]\.[01234567]\.[012345678])(.*)/', $code, $matches)){
            $ret['code'] = $matches[1];
            $ret['text'] = $matches[2];
        }
        else if(preg_match('/([245][01234567][012345678])(.*)/', $code, $matches)){
            preg_match_all("/./", $matches[1], $out);
            $ret['code'] = $out[0];
            $ret['text'] = $matches[2];
        }
        return $ret;
    }

    function fetch_status_messages($code){
        $ret = $this->format_status_code($code);
        $arr = explode('.', $ret['code']);

        $return_array = array();
        $return_array['status_code_info'] =  self::$status_code_classes[$arr[0]];
        $return_array['status_code_sub_info'] =  self::$status_code_subclasses[$arr[1] . "." . $arr[2]];

        return $return_array;
    }

    function get_action_from_status_code($code){
        if($code=='') return '';
        $ret = $this->format_status_code($code);
        $stat = $ret['code'][0];
        switch($stat){
            case(2):
                return 'success';
                break;
            case(4):
                return 'transient';
                break;
            case(5):
                return 'failed';
                break;
            default:
                return '';
                break;
        }
    }

    function decode_diagnostic_code($dcode){
        if(preg_match("/(\d\.\d\.\d)\s/", $dcode, $array)){
            return $array[1];
        }
        else if(preg_match("/(\d\d\d)\s/", $dcode, $array)){
            return $array[1];
        }
    }

    function is_a_bounce(){
        if(preg_match("/(mail delivery failed|failure notice|warning: message|delivery status notif|delivery failure|delivery problem|spam eater|returned mail|undeliverable|returned mail|delivery errors|mail status report|mail system error|failure delivery|delivery notification|delivery has failed|undelivered mail|returned email|returning message to sender|returned to sender|message delayed|mdaemon notification|mailserver notification|mail delivery system|nondeliverable mail|mail transaction failed)|auto.{0,20}reply|vacation|(out|away|on holiday).*office/i", $this->head_hash['Subject'])) return true;
        if(@preg_match('/auto_reply/',$this->head_hash['Precedence'])) return true;
        if(preg_match("/^(postmaster|mailer-daemon)\@?/i", $this->head_hash['From'])) return true;
        return false;
    }

    function find_email_addresses($first_body_part){
        // not finished yet.  This finds only one address.
        if(preg_match("/\b([A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/i", $first_body_part, $matches)){
            return array($matches[1]);
        }
        else
            return array();
    }


    // these functions are for feedback loops
    function is_an_ARF(){
        if(@preg_match('/feedback-report/',$this->head_hash['Content-type']['report-type'])) return true;
        if(@preg_match('/scomp/',$this->head_hash['X-loop'])) return true;
        if(isset($this->head_hash['X-hmxmroriginalrecipient']))  {
            $this->is_hotmail_fbl = TRUE;
            $this->recipient = $this->head_hash['X-hmxmroriginalrecipient'];
            return true;
        }
        if(isset($this->first_body_hash['X-hmxmroriginalrecipient']) )  {
            $this->is_hotmail_fbl = TRUE;
            $this->recipient = $this->first_body_hash['X-hmxmroriginalrecipient'];
            return true;
        }
        return false;
    }

    // look for common auto-responders
    function is_an_autoresponse() {
        if (preg_match('/^=\?utf-8\?B\?(.*?)\?=/', $this->head_hash['Subject'], $matches))
            $subj = base64_decode($matches[1]);
        else
            $subj = $this->head_hash['Subject'];
        foreach ($this->autorespondlist as $a) {
            if (preg_match("/$a/i", $subj)) {
//echo "$a , $subj"; exit;
                $this->autoresponse = $this->head_hash['Subject'];
                return TRUE;
            }
        }
        return FALSE;
    }



    // use a perl regular expression to find the web beacon
    public function find_web_beacon($body, $preg){
        if(!isset($preg) || !$preg)
            return "";
        if(preg_match($preg, $body, $matches))
            return $matches[1];
    }

    public function find_x_header($xheader){
        $xheader = ucfirst(strtolower($xheader));
        // check the header
        if(isset($this->head_hash[$xheader])){
            return $this->head_hash[$xheader];
        }
        // check the body too
        $tmp_body_hash = $this->standard_parser($this->body_hash);
        if(isset($tmp_body_hash[$xheader])){
            return $tmp_body_hash[$xheader];
        }
        return "";
    }

    private function find_fbl_recipients($fbl){
        if(isset($fbl['Original-rcpt-to'])){
            return $fbl['Original-rcpt-to'];
        }
        else if(isset($fbl['Removal-recipient'])){
            return trim(str_replace('--', '', $fbl['Removal-recipient']));
        }
        //else if(){
        //}
        //else {
        //}
    }

    private function strip_angle_brackets($recipient){
        $recipient = str_replace('<', '', $recipient);
        $recipient = str_replace('>', '', $recipient);
        return $recipient;
    }


    /*The syntax of the final-recipient field is as follows:
    "Final-Recipient" ":" address-type ";" generic-address
    */
    private function format_final_recipient_array($arr){
        $output = array('addr'=>'',
                        'type'=>'');
        if(strpos($arr[0], '@')!==FALSE){
            $output['addr'] = @$this->strip_angle_brackets($arr[0]);
            $output['type'] = (!empty($arr[1])) ? @trim($arr[1]) : 'unknown';
        }
        else{
            $output['type'] = @trim($arr[0]);
            $output['addr'] = @$this->strip_angle_brackets($arr[1]);
        }
        return $output;
    }
}
