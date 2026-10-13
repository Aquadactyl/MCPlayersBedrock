import React from "react";

export default function PlayerAvatar({ name }: { name: string }) {
  const hash = [...name].reduce((value, char) => (value * 31 + char.charCodeAt(0)) >>> 0, 0);
  const colors = ["#79b0a0", "#89a1c6", "#b79dca", "#c4a879", "#adbc82", "#c68e8e"];
  return (
    <svg className="mbp-avatar" viewBox="0 0 8 8" aria-hidden="true" shapeRendering="crispEdges">
      <path fill={colors[hash % colors.length]} d="M0 0h8v8H0z" />
      <path fill="#ffffff" opacity=".15" d="M0 0h8v2H0zM0 2h1v6H0z" />
      <path fill="#182221" d="M1 3h2v1H1zM5 3h2v1H5zM3 5h2v1H3z" />
      <path fill="#182221" opacity=".3" d="M0 7h8v1H0z" />
    </svg>
  );
}
