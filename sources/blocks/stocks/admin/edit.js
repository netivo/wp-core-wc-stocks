import { useWooBlockProps } from "@woocommerce/block-templates";
import {
  __experimentalCheckboxControl as CheckboxControl,
  __experimentalUseProductEntityProp as useProductEntityProp
} from '@woocommerce/product-editor';

export default function Edit({ attributes, context }) {
  const [value, setValue] = useProductEntityProp('meta_data.rudr_key', {
    postType: context.postType,
    fallbackValue: false
  });

  return (
    <div {...useWooBlockProps(attributes)}>
      <CheckboxControl
        label="Site 2"
        value={value || false}
        onChange={setValue}
      />
    </div>
  );
}