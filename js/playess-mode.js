/* /js/playess-mode.js
   Gemeinsame Logik für Open + Cover Mode (Timer, Load Round, Guess, Reveal, History)
*/
(() => {
  const cfg = window.PLAYESS_MODE;
  if (!cfg || !cfg.mode) {
    console.error('PLAYESS_MODE config missing.');
    return;
  }

  // ---------------------------
  // DOM helpers
  // ---------------------------
  const $ = (id) => (id ? document.getElementById(id) : null);

  const eloBadge = $(cfg.eloBadgeId);
  const eloValue = $(cfg.eloValueId);

  const placeholder = $(cfg.placeholderId);
  const reveal = $(cfg.revealId);
  const imgEl = $(cfg.imageId);
  const captionEl = $(cfg.captionId);

  const artistInput = $(cfg.artistInputId);
  const titleInput = $(cfg.titleInputId);

  const artistDelta = $(cfg.artistDeltaId);
  const titleDelta = $(cfg.titleDeltaId);

  const guessBtn = $(cfg.guessBtnId);
  const endBtn = $(cfg.endBtnId);

  const historyList = $(cfg.historyListId);
  const errorEl = $(cfg.errorId);

  const countdownEl = $(cfg.countdownId);

  const audioEl = cfg.hasAudio ? $(cfg.audioId) : null;
  const playBtn = cfg.hasAudio ? $(cfg.playBtnId) : null;

  // ---------------------------
  // State
  // ---------------------------
  const ROUND_SECONDS = Number(cfg.roundSeconds || 20);
  let currentRoundId = null;
  let inRevealPhase = false;
  let timerId = null;
  let timerLocked = false;
  let timeLeft = ROUND_SECONDS;

  // ---------------------------
  // UI
  // ---------------------------
  function showError(msg) {
    console.error(msg);
    if (errorEl) errorEl.textContent = msg;
  }

  function clearError() {
    if (errorEl) errorEl.textContent = '';
  }

  function setElo(value, deltaTotal) {
    if (!eloValue) return;
    eloValue.textContent = String(value);

    if (eloBadge && deltaTotal !== 0) {
      eloBadge.classList.add(deltaTotal > 0 ? 'elo-up' : 'elo-down');
      setTimeout(() => {
        eloBadge.classList.remove('elo-up', 'elo-down');
      }, 1500);
    }
  }

  function resetInputs() {
    if (artistInput) {
      artistInput.value = '';
      artistInput.classList.remove('input-correct', 'input-wrong');
    }
    if (titleInput) {
      titleInput.value = '';
      titleInput.classList.remove('input-correct', 'input-wrong');
    }
    if (artistDelta) artistDelta.textContent = '';
    if (titleDelta) titleDelta.textContent = '';
  }

  function resetCover() {
    if (reveal) reveal.classList.add('hidden');
    if (placeholder) placeholder.classList.remove('hidden');

    if (imgEl) {
      imgEl.src = '';
      if (cfg.blurClass) imgEl.classList.remove(cfg.blurClass);
    }
    if (captionEl) captionEl.textContent = '';
  }

  // ---------------------------
  // Timer
  // ---------------------------
  function stopTimer() {
    if (timerId) clearInterval(timerId);
    timerId = null;
  }

  function updateCountdown() {
    if (!countdownEl) return;
    countdownEl.textContent = `${Math.max(0, timeLeft)}s`;
  }

  function startTimer() {
    stopTimer();
    timerLocked = false;
    timeLeft = ROUND_SECONDS;
    updateCountdown();

    if (imgEl && cfg.blurClass) {
      imgEl.classList.remove(cfg.blurClass);
      void imgEl.offsetWidth;
      imgEl.classList.add(cfg.blurClass);
    }

    timerId = setInterval(() => {
      timeLeft--;
      updateCountdown();

      if (timeLeft <= 0) {
        stopTimer();
        submitGuess(true);
      }
    }, 1000);
  }

  // ---------------------------
  // Network helpers
  // ---------------------------
  async function fetchJsonSafe(url, options) {
    const res = await fetch(url, options);
    const text = await res.text();
    try {
      return { ok: true, data: JSON.parse(text) };
    } catch (e) {
      return { ok: false, error: 'Invalid JSON', raw: text };
    }
  }

  // ---------------------------
  // History Link (STRICT)
  // gleiche Priorität in beiden Modis:
  // appleMusicUrl -> itunesUrl -> trackViewUrl
  // ---------------------------
  function getHistoryLink(data) {
    return data?.appleMusicUrl || data?.itunesUrl || data?.trackViewUrl || null;
  }

  // ---------------------------
  // Load new round
  // ---------------------------
  async function loadNewRound() {
    stopTimer();
    inRevealPhase = false;
    timerLocked = false;

    clearError();
    resetInputs();
    resetCover();

    if (audioEl) {
      audioEl.pause();
      audioEl.currentTime = 0;
      audioEl.src = '';
    }

    try {
      const { ok, data, error, raw } = await fetchJsonSafe(cfg.newRoundUrl, { method: 'GET' });
      if (!ok) {
        showError(`${error} from newRoundUrl`);
        console.error('Raw response:', raw);
        return;
      }

      if (!data.success) {
        showError('Error getting round: ' + (data.error || 'unknown error'));
        return;
      }

      currentRoundId = data.round_id;

      if (audioEl && data.preview_url) {
        audioEl.src = data.preview_url;
      }

      if (!audioEl && data.cover_url && imgEl) {
        imgEl.src = data.cover_url;
        if (placeholder) placeholder.classList.add('hidden');
        if (reveal) reveal.classList.remove('hidden');
      }

      if (typeof data.elo !== 'undefined') {
        setElo(data.elo, 0);
      }

      startTimer();
    } catch (e) {
      showError('Network error while loading round.');
      console.error(e);
    }
  }

  // ---------------------------
  // Guess submit (normal + timeout)
  // ---------------------------
  async function submitGuess(isTimeout = false) {
    if (!currentRoundId || inRevealPhase || timerLocked) return;

    timerLocked = true;
    stopTimer();

    const artist = isTimeout ? '' : (artistInput ? artistInput.value : '');
    const title = isTimeout ? '' : (titleInput ? titleInput.value : '');

    const formData = new FormData();
    formData.append('mode', cfg.mode);
    formData.append('round_id', currentRoundId);
    formData.append('artist', artist);
    formData.append('title', title);

    try {
      const { ok, data, error, raw } = await fetchJsonSafe(cfg.guessUrl, {
        method: 'POST',
        body: formData
      });

      if (!ok) {
        showError(`${error} from guessUrl`);
        console.error('Raw response:', raw);
        timerLocked = false;
        return;
      }

      if (!data.success) {
        showError('Guess error: ' + (data.error || 'unknown error'));
        timerLocked = false;
        return;
      }

      inRevealPhase = true;

      if (imgEl && cfg.blurClass) imgEl.classList.remove(cfg.blurClass);

      if (artistInput) artistInput.classList.add(data.artistCorrect ? 'input-correct' : 'input-wrong');
      if (titleInput) titleInput.classList.add(data.titleCorrect ? 'input-correct' : 'input-wrong');

      if (artistDelta) artistDelta.textContent = data.deltaArtist > 0 ? '+1' : '-1';
      if (titleDelta) titleDelta.textContent = data.deltaTitle > 0 ? '+1' : '-1';

      if (typeof data.eloAfter !== 'undefined') {
        setElo(data.eloAfter, data.deltaTotal || 0);
      }

      // Reveal Cover (open)
      if (audioEl && data.cover && imgEl) {
        imgEl.src = data.cover;
        if (captionEl) captionEl.textContent = `${data.artist || ''} – ${data.title || ''}`;
        if (placeholder) placeholder.classList.add('hidden');
        if (reveal) reveal.classList.remove('hidden');
      }

      // Caption (cover)
      if (!audioEl && captionEl && (data.artist || data.title)) {
        captionEl.textContent = `${data.artist || ''} – ${data.title || ''}`;
      }

      // History Row
      if (historyList && data.cover && data.artist && data.title) {
        const row = document.createElement('article');
        row.className = 'leaderboard-row';

        const link = getHistoryLink(data);

        const btnHtml = link
          ? `<a class="apple-music-btn" href="${link}" target="_blank" rel="noopener noreferrer"> Music</a>`
          : `<span class="apple-music-btn apple-music-btn--disabled">No link</span>`;

        row.innerHTML = `
          <div class="leaderboard-left">
            <div class="leaderboard-avatar">
              <img src="${data.cover}" alt="Cover" class="leaderboard-avatar-cover">
            </div>
            <div class="leaderboard-userinfo">
              <div class="leaderboard-username">${data.artist}</div>
              <div class="leaderboard-meta">${data.title}</div>
            </div>
          </div>
          <div class="leaderboard-score">
            ${btnHtml}
          </div>
        `;

        historyList.prepend(row);
      }

      setTimeout(() => {
        loadNewRound();
      }, 5000);

    } catch (e) {
      showError('Network error while submitting guess.');
      console.error(e);
      timerLocked = false;
    }
  }

  // ---------------------------
  // Events
  // ---------------------------
  if (guessBtn) guessBtn.addEventListener('click', () => submitGuess(false));

  if (endBtn) {
    endBtn.addEventListener('click', () => {
      stopTimer();
      window.location.href = 'dashboard.php';
    });
  }

  if (playBtn && audioEl) {
    playBtn.addEventListener('click', () => {
      if (!audioEl.src) return;
      if (audioEl.paused) audioEl.play();
      else audioEl.pause();
    });
  }

  document.addEventListener('DOMContentLoaded', loadNewRound);
})();
