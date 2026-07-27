{{--
  DESIGN.md §11: the primary action sits in the lower half of a phone screen,
  where a thumb reaches. On a phone this bar pins itself to the bottom and
  clears the gesture area; from sm up it goes back to being an ordinary row at
  the end of the form.
--}}

<div {{ $attributes->class(
    'sticky bottom-0 -mx-4 mt-8 flex flex-wrap items-center gap-3 border-t border-ink-800 bg-ink-950/95 px-4 py-3'
    .' backdrop-blur-md safe-pb sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:px-0 sm:pb-0 sm:backdrop-blur-none'
) }}>
    {{ $slot }}
</div>
