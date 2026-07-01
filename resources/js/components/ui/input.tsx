import * as React from "react"

import { cn } from "@/lib/utils"

function Input({ className, type, ...props }: React.ComponentProps<"input">) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        "h-11 w-full min-w-0 rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-base text-slate-900 transition-[color,box-shadow,background-color] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-slate-700 placeholder:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-3 focus-visible:ring-blue-500/20 disabled:pointer-events-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60 aria-invalid:border-rose-500 aria-invalid:ring-3 aria-invalid:ring-rose-500/20 md:text-sm dark:aria-invalid:border-rose-500/50 dark:aria-invalid:ring-rose-500/40",
        className
      )}
      {...props}
    />
  )
}

export { Input }
