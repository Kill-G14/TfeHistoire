// Export d'un événement au format iCalendar (.ics) : ajout à Google Agenda, Apple Calendrier, Outlook...
// Écrit à la main (RFC 5545), sans bibliothèque.

const LINE_BREAK = "\r\n";
const DEFAULT_DURATION_HOURS = 2;

function pad(value) {
  return String(value).padStart(2, "0");
}

// Échappe les caractères réservés d'une valeur texte iCalendar
function escapeText(value) {
  return String(value ?? "")
    .replace(/\\/g, "\\\\")
    .replace(/;/g, "\\;")
    .replace(/,/g, "\\,")
    .replace(/\r?\n/g, "\\n");
}

// Une ligne iCalendar ne dépasse pas 75 caractères : les suivantes commencent par une espace
function foldLine(line) {
  const parts = [];
  let rest = line;
  while (rest.length > 73) {
    parts.push(rest.slice(0, 73));
    rest = " " + rest.slice(73);
  }
  parts.push(rest);
  return parts.join(LINE_BREAK);
}

// Date locale au format AAAAMMJJTHHMMSS (heure « flottante », sans fuseau)
function formatLocalDateTime(date) {
  return (
    `${date.getFullYear()}${pad(date.getMonth() + 1)}${pad(date.getDate())}` +
    `T${pad(date.getHours())}${pad(date.getMinutes())}${pad(date.getSeconds())}`
  );
}

function formatUtcDateTime(date) {
  return (
    `${date.getUTCFullYear()}${pad(date.getUTCMonth() + 1)}${pad(date.getUTCDate())}` +
    `T${pad(date.getUTCHours())}${pad(date.getUTCMinutes())}${pad(date.getUTCSeconds())}Z`
  );
}

// Construit le contenu du fichier .ics d'un événement
export function buildEventIcs(event, pageUrl) {
  const [year, month, day] = String(event.date).slice(0, 10).split("-").map(Number);
  const [hours = 0, minutes = 0] = String(event.time || "00:00")
    .split(":")
    .map(Number);

  const start = new Date(year, month - 1, day, hours, minutes, 0);
  const end = new Date(start.getTime() + DEFAULT_DURATION_HOURS * 60 * 60 * 1000);

  const location = [event.address, event.city, event.country]
    .filter(Boolean)
    .join(", ");

  const lines = [
    "BEGIN:VCALENDAR",
    "VERSION:2.0",
    "PRODID:-//MemoriaEventia//Calendrier//FR",
    "CALSCALE:GREGORIAN",
    "BEGIN:VEVENT",
    `UID:event-${event.id}@memoriaeventia.com`,
    `DTSTAMP:${formatUtcDateTime(new Date())}`,
    `DTSTART:${formatLocalDateTime(start)}`,
    `DTEND:${formatLocalDateTime(end)}`,
    `SUMMARY:${escapeText(event.title)}`,
    `LOCATION:${escapeText(location)}`,
    `DESCRIPTION:${escapeText(event.description)}`,
  ];

  if (pageUrl) {
    lines.push(`URL:${pageUrl}`);
  }

  lines.push("END:VEVENT", "END:VCALENDAR");

  return lines.map(foldLine).join(LINE_BREAK) + LINE_BREAK;
}

// Télécharge le fichier .ics d'un événement
export function downloadEventIcs(event, pageUrl) {
  const content = buildEventIcs(event, pageUrl);
  const blob = new Blob([content], { type: "text/calendar;charset=utf-8" });
  const url = URL.createObjectURL(blob);

  const link = document.createElement("a");
  link.href = url;
  link.download = `evenement-${event.id}.ics`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}
