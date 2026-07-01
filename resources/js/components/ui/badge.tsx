import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"
import { Slot } from "radix-ui"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "group/badge inline-flex h-5 w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-[6px] border border-transparent px-2 py-0.5 text-xs font-medium whitespace-nowrap transition-all focus-visible:border-blue-500 focus-visible:ring-[3px] focus-visible:ring-blue-500/20 has-data-[icon=inline-end]:pr-1.5 has-data-[icon=inline-start]:pl-1.5 aria-invalid:border-rose-500 aria-invalid:ring-rose-500/20 [&>svg]:pointer-events-none [&>svg]:size-3!",
  {
    variants: {
      variant: {
        default: "bg-blue-50 text-blue-700 [a]:hover:bg-blue-100",
        secondary:
          "bg-slate-100 text-slate-700 [a]:hover:bg-slate-200",
        destructive:
          "bg-rose-50 text-rose-700 focus-visible:ring-rose-500/20 [a]:hover:bg-rose-100",
        outline:
          "border-slate-200 text-slate-700 [a]:hover:bg-slate-50 [a]:hover:text-slate-950",
        ghost:
          "hover:bg-slate-100 hover:text-slate-700",
        link: "text-blue-700 underline-offset-4 hover:underline",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant = "default",
  asChild = false,
  ...props
}: React.ComponentProps<"span"> &
  VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot.Root : "span"

  return (
    <Comp
      data-slot="badge"
      data-variant={variant}
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }
