/* File 05 Future-18 learner workbench.
 * UI only: server-side File 00 authorization, version checks, blueprints,
 * protected learning state and clinical-safety boundaries remain authoritative.
 */
(() => {
  'use strict';
  const app = window.LSCH_F18_APP || {};
  const base = String(app.root || '');
  const panels = Array.from(document.querySelectorAll('[data-lsch-f18-workbench]'));
  if (!panels.length) return;

  const labModes = Object.freeze({
    'F05-FUT-04': 'case-simulation',
    'F05-FUT-05': 'remedy-differentiation',
    'F05-FUT-06': 'case-taking',
    'F05-FUT-07': 'repertory-reasoning',
    'F05-FUT-08': 'clinical-reasoning-map',
    'F05-FUT-09': 'viva',
    'F05-FUT-10': 'osce',
    'F05-FUT-13': 'evidence-appraisal'
  });

  async function api(path, method = 'GET', body = null) {
    const options = {
      method, credentials: 'same-origin', cache: 'no-store',
      headers: { 'X-WP-Nonce': String(app.nonce || ''), 'Content-Type': 'application/json' }
    };
    if (body !== null) options.body = JSON.stringify(body);
    const response = await fetch(base + path, options);
    let data = {};
    try { data = await response.json(); } catch (error) {
      throw new Error('Learning service returned an unreadable response.');
    }
    if (!response.ok) {
      const trace = data && data.data && data.data.trace_id ? ' (' + data.data.trace_id + ')' : '';
      throw new Error(String(data.message || 'The learning action is unavailable.') + trace);
    }
    return data;
  }

  function element(tag, text = '', className = '') {
    const node = document.createElement(tag);
    if (text !== '') node.textContent = String(text);
    if (className) node.className = className;
    return node;
  }
  function clear(node) { while (node.firstChild) node.removeChild(node.firstChild); }
  function append(parent, node) { parent.appendChild(node); return node; }
  function paragraph(parent, text) { return append(parent, element('p', text)); }
  function heading(parent, text) { return append(parent, element('h4', text)); }
  function validId(raw) { const number = Number(raw); return Number.isSafeInteger(number) && number > 0 ? number : 0; }
  function input(parent, labelText, name, type = 'text', required = true, maxLength = 2000) {
    const label = append(parent, element('label', labelText, 'lsch-f18-field'));
    const control = element(type === 'textarea' ? 'textarea' : 'input');
    if (type !== 'textarea') control.type = type;
    control.name = name;
    control.required = required;
    control.maxLength = maxLength;
    if (type === 'number') { control.min = '1'; control.step = '1'; }
    append(label, control);
    return control;
  }
  function select(parent, labelText, name, options) {
    const label = append(parent, element('label', labelText, 'lsch-f18-field'));
    const control = append(label, element('select'));
    control.name = name;
    options.forEach(([value, text]) => {
      const option = append(control, element('option', text));
      option.value = String(value);
    });
    return control;
  }
  function button(parent, text, type = 'button') {
    const b = append(parent, element('button', text, 'lsch-button'));
    b.type = type;
    return b;
  }
  function summary(parent, data, limit = 20) {
    if (data === null || data === undefined) { paragraph(parent, 'No approved records are currently available.'); return; }
    if (Array.isArray(data)) {
      if (!data.length) { paragraph(parent, 'No items are currently due or available.'); return; }
      const list = append(parent, element('ol', '', 'lsch-f18-records'));
      data.slice(0, limit).forEach((entry) => {
        const li = append(list, element('li'));
        const text = typeof entry === 'object' ? JSON.stringify(entry) : String(entry);
        append(li, element('span', text.slice(0, 900)));
      });
      if (data.length > limit) paragraph(parent, 'Showing the first ' + limit + ' records. Refine this view to see others.');
      return;
    }
    if (typeof data === 'object') {
      const list = append(parent, element('dl', '', 'lsch-f18-kv'));
      Object.keys(data).slice(0, limit).forEach((key) => {
        append(list, element('dt', key.replace(/_/g, ' ')));
        let value = data[key];
        if (typeof value === 'object') value = JSON.stringify(value);
        append(list, element('dd', String(value === null ? '' : value).slice(0, 900)));
      });
      return;
    }
    paragraph(parent, String(data));
  }

  function bindForm(form, handler, status) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const submit = form.querySelector('[type="submit"]');
      if (submit) submit.disabled = true;
      status.textContent = 'Working…';
      try { await handler(); status.textContent = 'Saved. The latest approved state is shown below.'; }
      catch (error) { status.textContent = error.message; }
      finally { if (submit) submit.disabled = false; }
    });
  }
  function listRecords(parent, rows, titleKey, action) {
    if (!Array.isArray(rows) || !rows.length) { paragraph(parent, 'No records are currently available.'); return; }
    const list = append(parent, element('ol', '', 'lsch-f18-records'));
    rows.slice(0, 50).forEach((row) => {
      const item = append(list, element('li', '', 'lsch-f18-record'));
      append(item, element('strong', String(row[titleKey] || row.competency_key || row.item_type || row.mode || 'Learning record')));
      const details = append(item, element('details'));
      append(details, element('summary', 'View my record'));
      summary(details, row, 15);
      if (action) action(item, row);
    });
  }

  function initialize(root) {
    const tool = root.querySelector('[data-lsch-f18-tool]');
    const panel = root.querySelector('[data-lsch-f18-panel]');
    const status = root.querySelector('[data-lsch-f18-status]');
    if (!tool || !panel || !status) return;
    let viewSequence = 0;
    const current = () => Number(tool.value.slice(-2));
    const noClinicalRecords = 'Use only simulated or de-identified educational information. Never submit a real patient record, diagnosis or emergency request.';

    async function reload(expected) {
      const requestSequence = ++viewSequence;
      clear(panel); status.textContent = 'Loading approved learning data…';
      try {
        await draw(expected, requestSequence);
        if (requestSequence === viewSequence) status.textContent = '';
      } catch (error) {
        if (requestSequence === viewSequence) { clear(panel); paragraph(panel, error.message); status.textContent = error.message; }
      }
    }
    async function showRead(endpoint, field, title, action) {
      const data = await api(endpoint);
      heading(panel, title);
      const items = field ? data[field] : data;
      if (Array.isArray(items)) listRecords(panel, items, 'competency_key', action);
      else summary(panel, items);
    }

    function reviewActions(parent, row) {
      if (!validId(row.id) || !validId(row.version)) return;
      const form = append(parent, element('form', '', 'lsch-f18-inline-form'));
      const quality = select(form, 'Recall quality', 'quality', [
        ['0', '0 — No recall'], ['1', '1 — Difficult'], ['2', '2 — Partial'],
        ['3', '3 — Adequate'], ['4', '4 — Good'], ['5', '5 — Confident']
      ]);
      button(form, 'Record review', 'submit');
      bindForm(form, async () => {
        await api('future18/review/' + validId(row.id) + '/result', 'PUT', { quality: Number(quality.value), version: Number(row.version) });
        await reload(tool.value);
      }, status);
    }

    function portfolioActions(parent, row) {
      if (!validId(row.id) || !validId(row.version)) return;
      const form = append(parent, element('form', '', 'lsch-f18-inline-form'));
      const visibility = select(form, 'Visibility', 'visibility', [
        ['private', 'Private'], ['shareable_by_consent', 'Eligible for explicit consent-based sharing']
      ]);
      visibility.value = row.visibility || 'private';
      button(form, 'Save visibility', 'submit');
      bindForm(form, async () => {
        await api('future18/portfolio/' + validId(row.id) + '/visibility', 'PUT', { visibility: visibility.value, version: Number(row.version) });
        await reload(tool.value);
      }, status);
    }

    function impactActions(parent, row) {
      if (!validId(row.id)) return;
      const form = append(parent, element('form', '', 'lsch-f18-inline-form'));
      paragraph(form, 'A targeted review with passing evidence is required before this item may be resolved.');
      button(form, 'Verify re-study and resolve', 'submit');
      bindForm(form, async () => {
        await api('future18/change-impact/' + validId(row.id) + '/resolve', 'PUT', {});
        await reload(tool.value);
      }, status);
    }

    async function lab(slug, sequence) {
      heading(panel, 'Governed practice — ' + slug.replace(/-/g, ' '));
      paragraph(panel, noClinicalRecords);
      const loader = append(panel, element('form', '', 'lsch-f18-forms'));
      const lesson = input(loader, 'Approved lesson ID', 'lesson_id', 'number', true, 12);
      button(loader, 'Open approved educational blueprint', 'submit');
      const exercise = append(panel, element('div', '', 'lsch-f18-exercise'));
      bindForm(loader, async () => {
        const lessonId = validId(lesson.value);
        if (!lessonId) throw new Error('Enter a valid lesson ID.');
        const blueprint = await api('future18/lab/' + slug + '/blueprint?lesson_id=' + lessonId);
        if (sequence !== viewSequence) return;
        clear(exercise);
        if (blueprint.instructions) paragraph(exercise, blueprint.instructions);
        const answerForm = append(exercise, element('form', '', 'lsch-f18-forms'));
        const fields = [];
        (Array.isArray(blueprint.fields) ? blueprint.fields : []).slice(0, 15).forEach((field) => {
          if (!field.key || !/^[a-z0-9_-]{1,90}$/.test(String(field.key))) return;
          const control = input(answerForm, field.prompt || field.key, field.key, 'textarea', true, 4000);
          fields.push([field.key, control]);
        });
        if (!fields.length) { paragraph(answerForm, 'No approved questions are available.'); return; }
        button(answerForm, 'Submit educational response', 'submit');
        bindForm(answerForm, async () => {
          const response = {};
          fields.forEach(([key, field]) => { response[key] = String(field.value || ''); });
          const result = await api('future18/lab/' + slug, 'POST', { source_type: 'lesson', source_id: lessonId, response });
          const receipt = append(exercise, element('div', '', 'lsch-f18-result'));
          paragraph(receipt, 'Recorded: ' + String(result.public_id || result.id || '') + ' — ' + String(result.status || 'pending'));
          if (result.status === 'awaiting_assessor' || blueprint.manual_assessment) paragraph(receipt, String(app.errors && app.errors.pending || 'Independent human grading is required.'));
          if (result.status === 'graded') paragraph(receipt, 'Educational score: ' + Number(result.score || 0) + '%');
          if (Array.isArray(result.feedback)) summary(receipt, result.feedback, 15);
          answerForm.querySelectorAll('textarea,button').forEach((node) => { node.disabled = true; });
        }, status);
      }, status);
    }

    async function draw(feature, sequence) {
      const number = Number(feature.slice(-2));
      if (number === 1) {
        const data = await api('future18/mastery');
        heading(panel, 'My competency mastery');
        if (data.summary) summary(panel, data.summary);
        listRecords(panel, data.competencies || [], 'competency_key');
      } else if (number === 2) {
        await showRead('future18/review-queue?limit=50', '', 'Review items due now', reviewActions);
      } else if (number === 3) {
        heading(panel, 'Create a private recall card');
        const form = append(panel, element('form', '', 'lsch-f18-forms'));
        const prompt = input(form, 'Learning prompt', 'prompt', 'textarea', true, 4000);
        const answer = input(form, 'My answer', 'answer', 'textarea', true, 8000);
        const source = input(form, 'Source lesson ID (optional)', 'source_id', 'number', false, 12);
        const competency = input(form, 'Competency key (optional)', 'competency_key', 'text', false, 96);
        button(form, 'Save my flashcard', 'submit');
        bindForm(form, async () => {
          await api('future18/flashcards', 'POST', {
            prompt: prompt.value, answer: answer.value, source_type: 'lesson',
            source_id: source.value, competency_key: competency.value
          });
          await reload(feature);
        }, status);
        paragraph(panel, 'Only your own learning notes should be entered. Do not copy restricted textbook material.');
        const reviews = await api('future18/review-queue?type=flashcard&limit=30');
        heading(panel, 'Flashcards due');
        listRecords(panel, reviews, 'prompt', reviewActions);
      } else if (labModes[feature]) {
        await lab(labModes[feature], sequence);
      } else if (number === 11) {
        heading(panel, 'Explainable personal learning plan');
        const goal = select(panel, 'Learning goal', 'goal', [
          ['balanced_mastery', 'Balanced mastery'], ['beginner', 'Foundation'],
          ['materia_medica', 'Materia Medica'], ['case_taking', 'Case-taking'],
          ['clinical_mastery', 'Clinical education'], ['teacher_training', 'Teacher training'], ['research', 'Research']
        ]);
        const output = append(panel, element('div'));
        const refresh = button(panel, 'Preview path without saving');
        const save = button(panel, 'Save this learning path');
        const showPlan = async (persist) => {
          const data = persist
            ? await api('future18/learning-path', 'PUT', { goal: goal.value })
            : await api('future18/learning-path?goal=' + encodeURIComponent(goal.value));
          clear(output); summary(output, data, 20);
        };
        refresh.addEventListener('click', async () => { try { await showPlan(false); } catch (error) { status.textContent = error.message; } });
        save.addEventListener('click', async () => { try { await showPlan(true); status.textContent = 'Learning path saved.'; } catch (error) { status.textContent = error.message; } });
        await showPlan(false);
      } else if (number === 12) {
        await showRead('future18/mistakes?limit=50', '', 'Private learning mistake book', reviewActions);
      } else if (number === 14) {
        await showRead('future18/portfolio?limit=50', '', 'My competency portfolio', portfolioActions);
      } else if (number === 15) {
        const data = await api('future18/mentorship');
        heading(panel, 'Active and historical mentorship');
        listRecords(panel, data, 'status', (parent, record) => {
          if (record.status !== 'active' || !validId(record.id) || !validId(record.version)) return;
          const form = append(parent, element('form', '', 'lsch-f18-inline-form'));
          const feedback = input(form, 'Mentor feedback (authorized mentors only)', 'feedback', 'textarea', true, 4000);
          button(form, 'Record mentoring feedback', 'submit');
          bindForm(form, async () => {
            await api('future18/mentorship/' + validId(record.id) + '/feedback', 'PUT', {
              feedback: { text: feedback.value }, version: Number(record.version)
            });
            await reload(feature);
          }, status);
        });
      } else if (number === 16) {
        heading(panel, 'Continuing professional development');
        const form = append(panel, element('form', '', 'lsch-f18-forms'));
        paragraph(form, 'Recording CPD is available only to eligible verified practitioners; verification requires an independent qualified reviewer.');
        const minutes = input(form, 'Activity duration (minutes)', 'minutes', 'number', true, 5);
        const competency = input(form, 'Competency key', 'competency_key', 'text', false, 96);
        const description = input(form, 'Learning evidence (brief)', 'evidence', 'textarea', true, 4000);
        button(form, 'Record CPD activity', 'submit');
        bindForm(form, async () => {
          await api('future18/cpd', 'POST', {
            activity_type: 'learning', minutes: Number(minutes.value),
            competency_key: competency.value, evidence: { summary: description.value }
          });
          await reload(feature);
        }, status);
        const data = await api('future18/cpd');
        heading(panel, 'My CPD records');
        listRecords(panel, data, 'activity_type');
      } else if (number === 17) {
        heading(panel, 'Source-grounded Socratic tutor — File 16');
        paragraph(panel, 'Educational answers require approved citations. No diagnosis, prescription or emergency-care authority is delegated.');
        const form = append(panel, element('form', '', 'lsch-f18-forms'));
        const lesson = input(form, 'Approved lesson ID', 'lesson_id', 'number', true, 12);
        const question = input(form, 'My learning question', 'question', 'textarea', true, 4000);
        const stage = select(form, 'Teaching stage', 'stage', [
          ['question', 'Ask'], ['hint', 'Hint'], ['explain', 'Explain'],
          ['challenge', 'Challenge'], ['recap', 'Recap']
        ]);
        button(form, 'Ask the learning tutor', 'submit');
        const answers = append(panel, element('div', '', 'lsch-f18-result'));
        bindForm(form, async () => {
          if (!validId(lesson.value)) throw new Error('A valid approved lesson ID is required.');
          const data = await api('future18/tutor', 'POST', {
            lesson_id: validId(lesson.value), question: question.value, stage: stage.value
          });
          clear(answers);
          paragraph(answers, data.text || 'No source-grounded answer was returned.');
          heading(answers, 'Verified citations');
          const citations = append(answers, element('ul'));
          (Array.isArray(data.citations) ? data.citations : []).forEach((citation) => {
            const item = append(citations, element('li', String(citation.title || citation.object_id || 'Approved source')));
            if (typeof citation.url === 'string' && /^https:\/\//i.test(citation.url)) {
              const link = append(item, element('a', 'Open approved source'));
              link.href = citation.url;
              link.rel = 'noopener noreferrer';
            }
          });
        }, status);
      } else if (number === 18) {
        await showRead('future18/change-impact?status=pending', '', 'Mandatory learning re-study', impactActions);
      } else {
        heading(panel, 'No approved activity selected.');
      }
    }

    tool.addEventListener('change', () => reload(tool.value));
    reload(tool.value);
  }

  panels.forEach(initialize);
})();