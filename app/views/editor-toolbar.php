<?php
/**
 * Composer toolbar: Bold / Italic / Underline + an emoji picker.
 * Include it just above a <textarea>, having set $editorTarget to that
 * textarea's id (the textarea also needs  data-editor-field  for shortcuts):
 *
 *   <?php $editorTarget = 'post-body'; include __DIR__ . '/../../app/views/editor-toolbar.php'; ?>
 *   <textarea id="post-body" name="body" data-editor-field></textarea>
 *
 * The buttons insert the same markers the server renders (**bold**, *italic*,
 * ++underline++) — the field stays a plain textarea, so nothing new is trusted.
 */
$editorTarget = $editorTarget ?? 'post-body';
$panelId      = $editorTarget . '-emoji';

/** Curated emoji, grouped. Labels double as the search text. */
$emojiGroups = [
    'Smileys' => [
        '😀' => 'grinning smile happy', '😊' => 'smiling blush happy', '🙂' => 'slight smile',
        '😄' => 'laughing happy joy', '😅' => 'sweat smile relief', '😂' => 'laughing tears joy',
        '🥲' => 'happy tear touched', '😌' => 'relieved calm content', '😍' => 'heart eyes love',
        '🥰' => 'loving hearts affection', '😉' => 'wink', '🤗' => 'hug hugging warm',
        '🤔' => 'thinking wondering hmm', '🤨' => 'raised eyebrow sceptical doubt', '😐' => 'neutral straight face',
        '😴' => 'sleeping tired sleep', '🥱' => 'yawning tired morning', '😮' => 'surprised wow open mouth',
        '😢' => 'sad crying tear', '😭' => 'sobbing crying sad', '🥹' => 'holding back tears moved',
        '😬' => 'grimace awkward', '🙃' => 'upside down irony', '😎' => 'sunglasses cool',
        '🤍' => 'white heart', '🫶' => 'heart hands love', '🙏' => 'pray thanks grateful please',
    ],
    'Gestures & people' => [
        '👋' => 'wave hello goodbye', '👍' => 'thumbs up yes good', '👎' => 'thumbs down no',
        '👏' => 'clap applause bravo', '🙌' => 'raised hands celebrate praise', '🤝' => 'handshake deal agree',
        '✌️' => 'peace victory', '🤞' => 'fingers crossed hope luck', '💪' => 'strong muscle strength',
        '👀' => 'eyes look watching', '🫡' => 'salute respect', '🤷' => 'shrug who knows',
        '☝️' => 'point up one', '👉' => 'point right', '✍️' => 'writing hand write',
        '👨‍👩‍👧' => 'family parents child', '👶' => 'baby', '🧑‍🤝‍🧑' => 'people friends together',
        '🧠' => 'brain mind thinking', '❤️' => 'red heart love', '💛' => 'yellow heart',
        '💙' => 'blue heart', '💚' => 'green heart', '🧡' => 'orange heart',
        '💜' => 'purple heart', '💖' => 'sparkling heart', '💭' => 'thought bubble thinking',
    ],
    'Nature & weather' => [
        '🌅' => 'sunrise morning dawn', '🌄' => 'sunrise mountains morning', '☀️' => 'sun sunny bright',
        '🌤️' => 'sun behind cloud', '⛅' => 'partly cloudy', '🌧️' => 'rain rainy cloud',
        '⛈️' => 'storm thunder', '🌈' => 'rainbow hope', '❄️' => 'snowflake cold winter',
        '🌙' => 'moon night crescent', '⭐' => 'star', '✨' => 'sparkles shine magic',
        '🌸' => 'cherry blossom flower spring', '🌼' => 'daisy flower', '🌻' => 'sunflower',
        '🌹' => 'rose flower love', '🌱' => 'seedling growth new', '🍀' => 'four leaf clover luck',
        '🌳' => 'tree', '🍂' => 'fallen leaves autumn', '🐦' => 'bird', '🦋' => 'butterfly change',
        '🐶' => 'dog puppy', '🐱' => 'cat kitten', '🌍' => 'earth world globe', '🔥' => 'fire hot',
    ],
    'Food & drink' => [
        '☕' => 'coffee cup hot drink morning', '🍵' => 'tea green tea', '🥛' => 'milk glass',
        '🧊' => 'ice cube iceberg', '🥐' => 'croissant breakfast', '🍞' => 'bread toast',
        '🥞' => 'pancakes breakfast', '🍳' => 'cooking egg breakfast', '🍎' => 'apple fruit',
        '🍌' => 'banana', '🍊' => 'orange fruit', '🍓' => 'strawberry', '🍰' => 'cake slice birthday',
        '🎂' => 'birthday cake celebrate', '🍪' => 'cookie biscuit', '🍫' => 'chocolate',
        '🍽️' => 'plate cutlery meal dinner', '🥂' => 'cheers toast celebrate', '🍯' => 'honey',
    ],
    'Life & objects' => [
        '🎉' => 'party celebrate tada', '🎊' => 'confetti celebrate', '🎁' => 'gift present',
        '🎈' => 'balloon party', '🕯️' => 'candle memory', '📖' => 'open book reading',
        '📚' => 'books reading', '📝' => 'memo note writing', '✏️' => 'pencil write',
        '📷' => 'camera photo', '🖼️' => 'framed picture photograph memory', '📻' => 'radio',
        '⌚' => 'watch time', '⏳' => 'hourglass time waiting', '🕰️' => 'clock time',
        '🔑' => 'key', '🧩' => 'puzzle piece', '💡' => 'light bulb idea', '🔔' => 'bell reminder',
        '💳' => 'credit card money payment', '💰' => 'money bag', '🎵' => 'music note',
        '📞' => 'phone call', '💬' => 'speech bubble comment talk', '❓' => 'question mark',
        '❗' => 'exclamation mark', '✅' => 'check tick done yes', '🚗' => 'car drive',
        '✈️' => 'plane travel flight', '🏡' => 'home house', '🛣️' => 'road journey freedom',
        '🕊️' => 'dove peace freedom', '🧳' => 'luggage travel', '🪞' => 'mirror reflection self',
    ],
];
?>
<div class="editor-bar" data-editor-target="<?= e($editorTarget) ?>">
  <div class="editor-btns">
    <button type="button" class="ed-btn ed-btn--b" data-editor-cmd="bold"
            title="Bold (Ctrl+B) — wraps the selected text in **" aria-label="Bold">B</button>
    <button type="button" class="ed-btn ed-btn--i" data-editor-cmd="italic"
            title="Italic (Ctrl+I) — wraps the selected text in *" aria-label="Italic">I</button>
    <button type="button" class="ed-btn ed-btn--u" data-editor-cmd="underline"
            title="Underline (Ctrl+U) — wraps the selected text in ++" aria-label="Underline">U</button>

    <span class="emoji-wrap">
      <button type="button" class="ed-btn ed-btn--emoji" data-toggle="<?= e($panelId) ?>"
              title="Insert an emoji" aria-label="Insert emoji">🙂 <span class="ed-caret">▾</span></button>

      <div class="emoji-panel" id="<?= e($panelId) ?>">
        <label class="emoji-search">
          <span class="sr-only">Search emoji</span>
          <input type="text" data-emoji-search placeholder="Search emoji…" autocomplete="off">
        </label>
        <div class="emoji-scroll">
          <?php foreach ($emojiGroups as $groupName => $items): ?>
            <div class="emoji-group">
              <div class="emoji-group-h"><?= e($groupName) ?></div>
              <div class="emoji-grid">
                <?php foreach ($items as $char => $label): ?>
                  <button type="button" class="emoji-btn" data-editor-emoji="<?= e($char) ?>"
                          aria-label="<?= e($label) ?>" title="<?= e($label) ?>"><?= e($char) ?></button>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </span>
  </div>
  <span class="editor-hint">Select text, then tap <b>B</b> / <i>I</i> / <u>U</u></span>
</div>
