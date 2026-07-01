import * as React from "react"

import { cn } from "@/lib/utils"

function Textarea({ className, ...props }: React.ComponentProps<"textarea">) {
  return (
    <textarea
      data-slot="textarea"
      className={cn(
        "flex field-sizing-content min-h-24 w-full resize-none rounded-[8px] border border-slate-200 bg-white px-3 py-3 text-base text-slate-900 transition-[color,box-shadow,background-color] outline-none placeholder:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-3 focus-visible:ring-blue-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60 aria-invalid:border-rose-500 aria-invalid:ring-3 aria-invalid:ring-rose-500/20 md:text-sm dark:aria-invalid:border-rose-500/50 dark:aria-invalid:ring-rose-500/40",
        className
      )}
      {...props}
    />
  )
}

export { Textarea }
