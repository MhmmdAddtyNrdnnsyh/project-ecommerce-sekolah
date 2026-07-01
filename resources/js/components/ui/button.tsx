import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"
import { Slot } from "radix-ui"

import { cn } from "@/lib/utils"

const buttonVariants = cva(
  "group/button inline-flex shrink-0 items-center justify-center rounded-[8px] border border-transparent bg-clip-padding text-sm font-medium whitespace-nowrap transition-all outline-none select-none focus-visible:border-blue-500 focus-visible:ring-3 focus-visible:ring-blue-500/20 active:not-aria-[haspopup]:translate-y-px disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-rose-500 aria-invalid:ring-3 aria-invalid:ring-rose-500/20 dark:aria-invalid:border-rose-500/50 dark:aria-invalid:ring-rose-500/40 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
  {
    variants: {
      variant: {
        default: "bg-blue-600 text-white hover:bg-blue-700",
        outline:
          "border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-950 aria-expanded:bg-slate-50 aria-expanded:text-slate-950 dark:bg-transparent dark:hover:bg-input/30",
        secondary:
          "bg-slate-100 text-slate-700 hover:bg-slate-200 aria-expanded:bg-slate-100 aria-expanded:text-slate-900",
        ghost:
          "text-slate-700 hover:bg-slate-100 hover:text-slate-950 aria-expanded:bg-slate-100 aria-expanded:text-slate-950 dark:hover:bg-muted/50",
        destructive:
          "bg-rose-600 text-white hover:bg-rose-700 focus-visible:border-rose-500 focus-visible:ring-rose-500/20 dark:bg-rose-600 dark:hover:bg-rose-700 dark:focus-visible:ring-rose-500/40",
        link: "text-blue-700 underline-offset-4 hover:underline",
      },
      size: {
        default:
          "h-10 gap-1.5 px-3.5 has-data-[icon=inline-end]:pr-3 has-data-[icon=inline-start]:pl-3",
        xs: "h-6 gap-1 px-2.5 text-xs has-data-[icon=inline-end]:pr-2 has-data-[icon=inline-start]:pl-2 [&_svg:not([class*='size-'])]:size-3",
        sm: "h-8 gap-1 px-3 has-data-[icon=inline-end]:pr-2 has-data-[icon=inline-start]:pl-2",
        lg: "h-11 gap-1.5 px-4 has-data-[icon=inline-end]:pr-3 has-data-[icon=inline-start]:pl-3",
        icon: "size-10",
        "icon-xs": "size-6 [&_svg:not([class*='size-'])]:size-3",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant = "default",
  size = "default",
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot.Root : "button"

  return (
    <Comp
      data-slot="button"
      data-variant={variant}
      data-size={size}
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
